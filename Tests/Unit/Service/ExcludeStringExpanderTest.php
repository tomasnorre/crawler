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

use AOE\Crawler\Service\ExcludeStringExpander;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExcludeStringExpander::class)]
class ExcludeStringExpanderTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[DataProvider('expandWithoutSubpagesDataProvider')]
    #[Test]
    public function expandReturnsPageIdsWithoutSubpages(string $excludeString, array $expected): void
    {
        // No page tree is built, which would fail without a backend user
        self::assertSame($expected, (new ExcludeStringExpander())->expand($excludeString));
    }

    public static function expandWithoutSubpagesDataProvider(): iterable
    {
        yield 'Empty' => ['', []];
        yield 'Single page' => ['5', [5]];
        yield 'Pages with spaces and empty parts' => [' 5, ,7 ', [5, 7]];
        yield 'Duplicate pages keep their first key' => ['5,7,5', [
            0 => 5,
            1 => 7,
        ]];
    }

    #[DataProvider('expandWithSubpagesDataProvider')]
    #[Test]
    public function expandAddsSubpagesOfDepth(string $excludeString, int $expectedPageId, int $expectedDepth): void
    {
        $this->setBackendUser();
        $this->addPageTree($expectedPageId, $expectedDepth, [11, 12]);

        self::assertSame([5, 11, 12], (new ExcludeStringExpander())->expand($excludeString));
    }

    public static function expandWithSubpagesDataProvider(): iterable
    {
        yield 'Depth 2' => ['5+2', 5, 2];
        yield 'Depth with spaces' => ['5 + 2', 5, 2];
        yield 'All subpages' => ['5+', 5, 99];
        yield 'All subpages with depth 0' => ['5+0', 5, 99];
    }

    #[Test]
    public function expandBuildsEachPageTreeOnce(): void
    {
        $this->setBackendUser();
        $this->addPageTree(5, 1, [11, 5]);
        $this->addPageTree(6, 1, [21]);
        $subject = new ExcludeStringExpander();

        self::assertSame([
            0 => 5,
            1 => 11,
            3 => 6,
            4 => 21,
        ], $subject->expand('5+1,6+1,5+1'));
        self::assertSame([5, 11], $subject->expand('5+1'));
        self::assertSame([5, 11], $subject->expand('5+1'));
    }

    private function setBackendUser(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('backendCheckLogin');
        $backendUser->method('getPagePermsClause')->with(Permission::PAGE_SHOW)->willReturn('perms_clause');
        $GLOBALS['BE_USER'] = $backendUser;
    }

    /**
     * @param list<int> $subpageIds
     */
    private function addPageTree(int $pageId, int $depth, array $subpageIds): void
    {
        $tree = $this->createMock(PageTreeView::class);
        $tree->expects(self::once())->method('init')->with('AND perms_clause');
        $tree->expects(self::once())->method('getTree')->with($pageId, $depth)->willReturn(count($subpageIds));
        $tree->tree = array_map(static fn (int $uid): array => [
            'row' => [
                'uid' => (string) $uid,
            ],
        ], $subpageIds);
        GeneralUtility::addInstance(PageTreeView::class, $tree);
    }
}
