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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(PageConfigurationResolver::class)]
class PageConfigurationResolverTest extends UnitTestCase
{
    private const CONFIGURATIONS = [
        'default' => [
            'subCfg' => [
                'key' => 'default',
            ],
        ],
    ];

    #[DataProvider('getConfigurationsForPageRowDataProvider')]
    #[Test]
    public function getConfigurationsForPageRow(
        array $pageRow,
        string|false $pageSkipMessage,
        array $expected,
        string $expectedSkipMessage
    ): void {
        $subject = $this->createSubject($pageSkipMessage);
        $subject->method('getConfigurationsForPage')->willReturn(self::CONFIGURATIONS);

        $skipMessage = 'Message from earlier calls';
        self::assertSame($expected, $subject->getConfigurationsForPageRow($pageRow, $skipMessage));
        self::assertSame($expectedSkipMessage, $skipMessage);
    }

    public static function getConfigurationsForPageRowDataProvider(): iterable
    {
        yield 'Page is crawled' => [
            'pageRow' => [
                'uid' => 2001,
            ],
            'pageSkipMessage' => false,
            'expected' => self::CONFIGURATIONS,
            'expectedSkipMessage' => '',
        ];
        yield 'Page uid is a string with an integer value' => [
            'pageRow' => [
                'uid' => '2001',
            ],
            'pageSkipMessage' => false,
            'expected' => self::CONFIGURATIONS,
            'expectedSkipMessage' => '',
        ];
        yield 'Page is skipped' => [
            'pageRow' => [
                'uid' => 2001,
            ],
            'pageSkipMessage' => 'Because page is hidden',
            'expected' => [],
            'expectedSkipMessage' => 'Because page is hidden',
        ];
        yield 'Page uid is not an integer' => [
            'pageRow' => [
                'uid' => 'string',
            ],
            'pageSkipMessage' => false,
            'expected' => [],
            'expectedSkipMessage' => 'PageUid "string" was not an integer',
        ];
        yield 'Page row has no uid' => [
            'pageRow' => [],
            'pageSkipMessage' => false,
            'expected' => [],
            'expectedSkipMessage' => "pageRow['uid'] is missing",
        ];
    }

    #[Test]
    public function getConfigurationsForPageRowPassesMountPoint(): void
    {
        $subject = $this->createSubject(false);
        $subject->expects(self::once())
            ->method('getConfigurationsForPage')
            ->with(2001, '5-12')
            ->willReturn(self::CONFIGURATIONS);

        $skipMessage = '';
        self::assertSame(
            self::CONFIGURATIONS,
            $subject->getConfigurationsForPageRow([
                'uid' => 2001,
            ], $skipMessage, '5-12')
        );
    }

    private function createSubject(string|false $pageSkipMessage): PageConfigurationResolver&MockObject
    {
        $pageService = $this->createStub(PageService::class);
        $pageService->method('checkIfPageShouldBeSkipped')->willReturn($pageSkipMessage);

        return $this->getMockBuilder(PageConfigurationResolver::class)
            ->setConstructorArgs([
                $pageService,
                $this->createStub(ConfigurationService::class),
                $this->createStub(ConfigurationRepository::class),
            ])
            ->onlyMethods(['getConfigurationsForPage'])
            ->getMock();
    }
}
