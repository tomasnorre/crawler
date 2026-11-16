<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Queue;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueEntryWriter::class)]
class QueueEntryWriterTest extends UnitTestCase
{
    #[Test]
    public function getConfigurationHashIgnoresExpandedParametersAndUrls(): void
    {
        $subject = $this->getAccessibleMock(QueueEntryWriter::class, null, [], '', false);

        $configuration = [
            'paramExpanded' => 'extendedParameter',
            'URLs' => 'URLs',
            'NotImportantParameter' => 'value not important',
        ];

        $originalCheckSum = md5(serialize($configuration));

        self::assertNotEquals($originalCheckSum, $subject->_call('getConfigurationHash', $configuration));

        unset($configuration['paramExpanded'], $configuration['URLs']);
        $newCheckSum = md5(serialize($configuration));
        self::assertEquals($newCheckSum, $subject->_call('getConfigurationHash', $configuration));
    }

    #[DataProvider('getConfigurationHashReturnsExpectedValueDataProvider')]
    #[Test]
    public function getConfigurationHashReturnsExpectedValue(array $configuration, string $expected): void
    {
        $subject = $this->getAccessibleMock(QueueEntryWriter::class, null, [], '', false);

        self::assertEquals($expected, $subject->_call('getConfigurationHash', $configuration));
    }

    public static function getConfigurationHashReturnsExpectedValueDataProvider(): iterable
    {
        yield 'Configuration with either paramExpanded nor URLs set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => '',
                'URLs' => '',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with only paramExpanded set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important',
                'URLs' => '',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with only URLS set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => '',
                'URLs' => 'Value not important',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with both paramExpanded and URLS set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important',
                'URLs' => 'Value not important',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with both paramExpanded and URLS set, will return same hash' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important, but different than test case before',
                'URLs' => 'Value not important, but different than test case before',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
    }

    #[Test]
    public function compileUrlListCollectsDownloadUrlsAndSkipsUrlsAlreadyHandledInTheRun(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true);
        $subject = $this->createSubjectForCompileUrlList();
        $configuration = $this->getConfiguration(['L=0', 'L=1']);

        $subject->compileUrlList($configuration, [
            'uid' => 5,
        ], $run);
        $urls = $subject->compileUrlList($configuration, [
            'uid' => 5,
        ], $run);

        self::assertSame([
            'https://example.com/?L=0' => 'https://example.com/?L=0',
            'https://example.com/?L=1' => 'https://example.com/?L=1',
        ], $run->getDownloadUrls());
        self::assertSame(2, $run->countHandledUrls());
        self::assertCount(2, $run->getUrlList());
        self::assertSame(
            '<em><span class="text-muted">https://example.com/?L=0</span></em><br>'
            . '<em><span class="text-muted">https://example.com/?L=1</span></em>',
            $urls
        );
    }

    #[Test]
    public function compileUrlListSpreadsScheduledTimeByRequestsPerMinute(): void
    {
        $run = new CrawlRun(1_000_050, 1);

        $this->createSubjectForCompileUrlList()->compileUrlList($this->getConfiguration(['L=0', 'L=1']), [
            'uid' => 5,
        ], $run);

        self::assertSame([
            '[' . date('Y-m-d H:i', 1_000_020) . '] https://example.com/?L=0',
            '[' . date('Y-m-d H:i', 1_000_080) . '] https://example.com/?L=1',
        ], $run->getUrlList());
        self::assertSame([], $run->getDownloadUrls());
    }

    #[Test]
    public function compileUrlListSkipsUrlsOfOtherProcessingInstructions(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true, processingInstructions: ['tx_other']);

        $urls = $this->createSubjectForCompileUrlList()->compileUrlList(
            $this->getConfiguration(['L=0'], 'tx_indexedsearch_reindex'),
            [
                'uid' => 5,
            ],
            $run
        );

        self::assertSame('', $urls);
        self::assertSame(0, $run->countHandledUrls());
        self::assertSame([], $run->getDownloadUrls());
    }

    private function createSubjectForCompileUrlList(): QueueEntryWriter
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';

        $urlService = $this->createStub(UrlService::class);
        $urlService->method('getUrlFromPageAndQueryParameters')->willReturnCallback(
            static fn (int $pageId, string $query): UriInterface => new Uri('https://example.com/?' . $query)
        );

        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionConfiguration')->willReturn([
            'enableTimeslot' => '1',
        ]);

        return new QueueEntryWriter(
            $this->createStub(QueueRepository::class),
            $this->createStub(EventDispatcherInterface::class),
            $urlService,
            new ProcessInstructionService(),
            $this->createStub(CrawlUrlMessageDispatcher::class),
            $extensionConfigurationProvider,
        );
    }

    private function getConfiguration(array $urls, string $procInstrFilter = ''): array
    {
        return [
            'subCfg' => [
                'key' => 'default',
                'procInstrFilter' => $procInstrFilter,
            ],
            'URLs' => $urls,
        ];
    }
}
