<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

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

use AOE\Crawler\Service\PageRootlineProvider;
use AOE\Crawler\Service\PageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(PageRootlineProvider::class)]
#[CoversClass(PageService::class)]
final class PageRootlineProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_excluded_from_crawling.csv');
    }

    #[Test]
    public function getRootlineWithExclusionFieldsStartsWithThePageAndEndsWithTheRootPage(): void
    {
        $subject = GeneralUtility::makeInstance(PageRootlineProvider::class);

        self::assertSame(
            [
                [
                    'uid' => 5,
                    'tx_crawler_exclude' => false,
                    'tx_crawler_exclude_subpages' => false,
                ],
                [
                    'uid' => 4,
                    'tx_crawler_exclude' => true,
                    'tx_crawler_exclude_subpages' => true,
                ],
                [
                    'uid' => 2,
                    'tx_crawler_exclude' => true,
                    'tx_crawler_exclude_subpages' => false,
                ],
                [
                    'uid' => 1,
                    'tx_crawler_exclude' => false,
                    'tx_crawler_exclude_subpages' => false,
                ],
            ],
            $subject->getRootlineWithExclusionFields(5)
        );
    }

    #[Test]
    public function getRootlineWithExclusionFieldsReturnsEmptyArrayForMissingPage(): void
    {
        $subject = GeneralUtility::makeInstance(PageRootlineProvider::class);

        self::assertSame([], $subject->getRootlineWithExclusionFields(999));
    }

    /**
     * Uses the page tree of Fixtures/pages_excluded_from_crawling.csv
     */
    #[DataProvider('checkIfPageShouldBeSkippedDataProvider')]
    #[Test]
    public function checkIfPageShouldBeSkipped(int $pageId, false|string $expected): void
    {
        $subject = GeneralUtility::makeInstance(PageService::class);

        self::assertSame($expected, $subject->checkIfPageShouldBeSkipped([
            'uid' => $pageId,
            'doktype' => 1,
        ]));
    }

    public static function checkIfPageShouldBeSkippedDataProvider(): iterable
    {
        yield 'Root is included' => [1, false];
        yield 'Page1 is excluded, without its subpages' => [2, 'Because page is excluded from crawling'];
        yield 'subpage1 is included' => [3, false];
        yield 'subpage2 is excluded, with its subpages' => [4, 'Because page is excluded from crawling'];
        yield 'subsubpage1 is excluded by subpage2' => [
            5,
            'Because parent page "4" is excluded from crawling with its subpages',
        ];
        yield 'subsubpage2 is excluded by subpage2' => [
            6,
            'Because parent page "4" is excluded from crawling with its subpages',
        ];
        yield 'subpage3 is included' => [7, false];
        yield 'Page2 is included' => [8, false];
        yield 'Page3 is included' => [9, false];
        yield 'Subpage of a deleted page that excluded its subpages is included' => [11, false];
    }
}
