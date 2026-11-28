<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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
use AOE\Crawler\Configuration\ExtensionSettings;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\QueueService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Tests\Unit\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueService::class)]
class QueueServiceTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']);
        parent::tearDown();
    }

    #[Test]
    public function addPageToQueueAddsUrlsOfAllConfigurationsOfPage(): void
    {
        $addedEntries = [];
        $this->createSubject($addedEntries, 3)->addPageToQueue(5, 1_000_060);

        self::assertSame(['default', 'indexing', 'other'], array_column($addedEntries, 'configuration'));
        self::assertSame([1_000_060, 1_000_060, 1_000_060], array_column($addedEntries, 'scheduled'));
        self::assertSame([5, 5, 5], array_column($addedEntries, 'page_id'));
        self::assertCount(1, array_unique(array_column($addedEntries, 'set_id')));
    }

    #[Test]
    public function addPageToQueueAddsAllConfigurationsIfProcessingInstructionsAreRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = [
            'indexed_search' => [
                'key' => 'tx_indexedsearch_reindex',
                'value' => 'Re-indexing',
            ],
        ];

        $addedEntries = [];
        $this->createSubject($addedEntries, 3)->addPageToQueue(5);

        self::assertSame(['default', 'indexing', 'other'], array_column($addedEntries, 'configuration'));
        self::assertSame(
            ['[""]', '["tx_indexedsearch_reindex"]', '["tx_other"]'],
            array_map(
                static fn (array $entry): string => json_encode(
                    json_decode($entry['parameters'], true)['procInstructions']
                ),
                $addedEntries
            )
        );
        self::assertSame([0, 0, 0], array_column($addedEntries, 'scheduled'));
    }

    #[Test]
    public function addPageToQueueUsesTheSameSetIdForEachCall(): void
    {
        $addedEntries = [];
        $subject = $this->createSubject($addedEntries, 6, 2);
        $subject->addPageToQueue(5);
        $subject->addPageToQueue(5);

        self::assertCount(1, array_unique(array_column($addedEntries, 'set_id')));
    }

    private function createSubject(array &$addedEntries, int $expectedEntries, int $expectedCalls = 1): QueueService
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';
        $pageRow = [
            'uid' => 5,
            'title' => 'Page',
        ];
        for ($i = 0; $i < $expectedCalls; $i++) {
            $pageRepository = $this->createMock(PageRepository::class);
            $pageRepository->expects(self::once())->method('getPage')->with(5, true)->willReturn($pageRow);
            GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        }
        $pageConfigurationResolver = $this->createMock(PageConfigurationResolver::class);
        $pageConfigurationResolver->expects(self::exactly($expectedCalls))->method('getConfigurationsForPageRow')
            ->with($pageRow)
            ->willReturn([
                'default' => $this->getConfiguration('default', ''),
                'indexing' => $this->getConfiguration('indexing', 'tx_indexedsearch_reindex'),
                'other' => $this->getConfiguration('other', 'tx_other'),
            ]);

        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->method('getDuplicateQueueItemsIfExists')->willReturn([]);
        $queueRepository->expects(self::exactly($expectedEntries))->method('add')->willReturnCallback(
            static function (array $fieldArray) use (&$addedEntries): int {
                $addedEntries[] = $fieldArray;
                return count($addedEntries);
            }
        );

        return new QueueService($this->createQueueEntryWriter($queueRepository), $pageConfigurationResolver);
    }

    private function getConfiguration(string $key, string $procInstrFilter): array
    {
        return [
            'subCfg' => [
                'key' => $key,
                'procInstrFilter' => $procInstrFilter,
            ],
            'URLs' => ['?id=5&config=' . $key],
        ];
    }

    private function createQueueEntryWriter(QueueRepository $queueRepository): QueueEntryWriter
    {
        $urlService = $this->createStub(UrlService::class);
        $urlService->method('getUrlFromPageAndQueryParameters')->willReturnCallback(
            static fn (int $pageId, string $query): Uri => new Uri('https://example.com/' . $query)
        );
        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionSettings')->willReturn(
            new ExtensionSettings(enableTimeslot: true)
        );

        return new QueueEntryWriter(
            $queueRepository,
            $this->createStub(EventDispatcherInterface::class),
            $urlService,
            new ProcessInstructionService(),
            $this->createStub(CrawlUrlMessageDispatcher::class),
            $extensionConfigurationProvider,
            new FrozenClock(1_000_000),
        );
    }
}
