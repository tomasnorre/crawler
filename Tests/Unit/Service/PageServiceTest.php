<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Event\ModifySkipPageEvent;
use AOE\Crawler\Service\PageRootlineProvider;
use AOE\Crawler\Service\PageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExtensionConfigurationProvider::class)]
#[CoversClass(ModifySkipPageEvent::class)]
#[CoversClass(PageService::class)]
class PageServiceTest extends UnitTestCase
{
    /**
     * The page tree of the exclusion tests, as uid => [pid, tx_crawler_exclude, tx_crawler_exclude_subpages]
     *
     *  Root (1)
     *  ├── Page1 (2)           excluded, subpages included
     *  │   ├── subpage1 (3)    included
     *  │   ├── subpage2 (4)    excluded with its subpages
     *  │   │   ├── subsubpage1 (5)  excluded by subpage2
     *  │   │   └── subsubpage2 (6)  excluded by subpage2
     *  │   │       └── subsubsubpage (7)  excluded by subpage2
     *  │   └── subpage3 (8)    included
     *  ├── Page2 (9)           included, "exclude subpages" set without "exclude"
     *  │   └── subpage4 (10)   included
     *  └── Page3 (11)          included
     */
    private const PAGE_TREE = [
        1 => [0, false, false],
        2 => [1, true, false],
        3 => [2, false, false],
        4 => [2, true, true],
        5 => [4, false, false],
        6 => [4, false, false],
        7 => [6, false, false],
        8 => [2, false, false],
        9 => [1, false, true],
        10 => [9, false, false],
        11 => [1, false, false],
    ];

    protected PageService $subject;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $modifySkipPageEvent = new ModifySkipPageEvent([]);
        $modifySkipPageEvent->setSkipped(false);

        $mockedEventDispatcher = $this->createStub(EventDispatcher::class);
        $mockedEventDispatcher->method('dispatch')->willReturn($modifySkipPageEvent);

        $this->subject = new PageService($mockedEventDispatcher, $this->createPageRootlineProviderStub());
    }

    #[DataProvider('checkIfPageShouldBeSkippedDataProvider')]
    #[Test]
    public function checkIfPageShouldBeSkipped(
        array $extensionSetting,
        array $pageRow,
        array $excludeDoktype,
        string $expected
    ): void {
        if (empty($expected)) {
            $expected = false;
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $extensionSetting;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;

        self::assertEquals($expected, $this->subject->checkIfPageShouldBeSkipped($pageRow));
    }

    public static function checkIfPageShouldBeSkippedDataProvider(): iterable
    {
        yield 'Page of doktype 1 - Standard' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => '',
        ];

        yield 'Extension Setting empty and page is hidden' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 1,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because page is hidden',
        ];

        yield 'Extension Setting crawl hidden pages and page is hidden' => [
            'extensionSetting' => [
                'crawlHiddenPages' => true,
            ],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 1,
            ],
            'excludeDoktype' => [],
            'expected' => '',
        ];

        yield 'Extension Setting do not crawl hidden pages and page is hidden' => [
            'extensionSetting' => [
                'crawlHiddenPages' => false,
            ],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 1,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because page is hidden',
        ];

        yield 'Extension Setting empty and hidden is not set for page' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
            ],
            'excludeDoktype' => [],
            'expected' => '',
        ];

        yield 'Page of doktype 3 - External Url' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 3,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "3" is not allowed',
        ];

        yield 'Page of doktype 4 - Shortcut' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 4,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "4" is not allowed',
        ];

        yield 'Page of doktype 155 - Custom' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 155,
                'hidden' => 0,
            ],
            'excludeDoktype' => [
                'custom' => 155,
            ],
            'expected' => 'Doktype "155" was excluded by excludeDoktype configuration key "custom"',
        ];

        yield 'Page of doktype 199 - Spacer' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 199,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "199" is not allowed',
        ];

        yield 'Page of doktype 254 - Folder' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 254,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "254" is not allowed',
        ];
    }

    #[DataProvider('checkIfPageShouldBeSkippedForExcludedPagesDataProvider')]
    #[Test]
    public function checkIfPageShouldBeSkippedForExcludedPages(int $pageId, false|string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];

        self::assertSame($expected, $this->subject->checkIfPageShouldBeSkipped([
            'uid' => $pageId,
            'doktype' => 1,
            'hidden' => 0,
        ]));
    }

    public static function checkIfPageShouldBeSkippedForExcludedPagesDataProvider(): iterable
    {
        yield 'Root page is included' => [1, false];
        yield 'Page excluded without its subpages is skipped' => [2, 'Because page is excluded from crawling'];
        yield 'Subpage of a page excluded without its subpages is included' => [3, false];
        yield 'Page excluded with its subpages is skipped' => [4, 'Because page is excluded from crawling'];
        yield 'Subpage of a page excluded with its subpages is skipped' => [
            5,
            'Because parent page "4" is excluded from crawling with its subpages',
        ];
        yield 'Second subpage of a page excluded with its subpages is skipped' => [
            6,
            'Because parent page "4" is excluded from crawling with its subpages',
        ];
        yield 'Page two levels below a page excluded with its subpages is skipped' => [
            7,
            'Because parent page "4" is excluded from crawling with its subpages',
        ];
        yield 'Sibling of a page excluded with its subpages is included' => [8, false];
        yield 'Page with "exclude subpages" but not excluded itself is included' => [9, false];
        yield 'Subpage of a page with "exclude subpages" but not excluded itself is included' => [10, false];
        yield 'Page without exclusion is included' => [11, false];
        yield 'Page without rootline is included' => [999, false];
    }

    /**
     * Used by the CrawlUrlHandler, which checks the exclusion again before crawling a queued URL
     */
    #[Test]
    public function getExclusionReasonReturnsTheReasonOfAParentPage(): void
    {
        self::assertSame(
            'Because parent page "4" is excluded from crawling with its subpages',
            $this->subject->getExclusionReason(6)
        );
        self::assertFalse($this->subject->getExclusionReason(3));
    }

    #[Test]
    public function checkIfPageShouldBeSkippedReturnsExclusionBeforeDispatchingTheEvent(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects(self::never())->method('dispatch');
        $subject = new PageService($eventDispatcher, $this->createPageRootlineProviderStub());

        self::assertSame(
            'Because page is excluded from crawling',
            $subject->checkIfPageShouldBeSkipped([
                'uid' => 2,
                'doktype' => 1,
            ])
        );
    }

    #[DataProvider('pageRowsWithoutPageUidDataProvider')]
    #[Test]
    public function checkIfPageShouldBeSkippedDoesNotReadRootlineWithoutPageUid(array $pageRow): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $pageRootlineProvider = $this->createMock(PageRootlineProvider::class);
        $pageRootlineProvider->expects(self::never())->method('getRootlineWithExclusionFields');
        $modifySkipPageEvent = new ModifySkipPageEvent([]);
        $modifySkipPageEvent->setSkipped(false);
        $eventDispatcher = $this->createStub(EventDispatcher::class);
        $eventDispatcher->method('dispatch')->willReturn($modifySkipPageEvent);
        $subject = new PageService($eventDispatcher, $pageRootlineProvider);

        self::assertFalse($subject->checkIfPageShouldBeSkipped($pageRow));
    }

    public static function pageRowsWithoutPageUidDataProvider(): iterable
    {
        yield 'Page uid is missing' => [[
            'doktype' => 1,
        ]];
        yield 'Page uid is 0' => [[
            'uid' => 0,
            'doktype' => 1,
        ]];
    }

    /**
     * Returns the rootlines of PAGE_TREE, the way PageRootlineProvider does: the page itself first, the root page last
     */
    private function createPageRootlineProviderStub(): PageRootlineProvider
    {
        $pageRootlineProvider = $this->createStub(PageRootlineProvider::class);
        $pageRootlineProvider->method('getRootlineWithExclusionFields')->willReturnCallback(
            static function (int $pageId): array {
                $rootline = [];
                while (isset(self::PAGE_TREE[$pageId])) {
                    [$pid, $exclude, $excludeSubpages] = self::PAGE_TREE[$pageId];
                    $rootline[] = [
                        'uid' => $pageId,
                        'tx_crawler_exclude' => $exclude,
                        'tx_crawler_exclude_subpages' => $excludeSubpages,
                    ];
                    $pageId = $pid;
                }
                return $rootline;
            }
        );
        return $pageRootlineProvider;
    }
}
