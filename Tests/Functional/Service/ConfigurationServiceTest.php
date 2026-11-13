<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\ExcludeStringExpander;
use AOE\Crawler\Service\ParameterExpander;
use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ConfigurationServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private ConfigurationService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ConfigurationService(
            $this->createStub(UrlService::class),
            $this->createStub(ConfigurationRepository::class),
            $this->createStub(GroupResolver::class),
            new ParameterExpander(),
            new ExcludeStringExpander(),
        );
    }

    #[Test]
    public function expandExcludeStringReturnsArraysOfIntegers(): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'foo';

        $excludeStringArray = $this->subject->expandExcludeString('1,2,4,6,8');

        foreach ($excludeStringArray as $excluded) {
            self::assertIsInt($excluded);
        }
    }

    #[Test]
    public function expandExcludeStringBuildsThePageTreeOnlyOncePerPageAndDepth(): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();

        $getTreeCalls = 0;
        // Enough page trees for building the tree of page 5 twice
        GeneralUtility::addInstance(PageTreeView::class, $this->createCountingPageTree($getTreeCalls));
        GeneralUtility::addInstance(PageTreeView::class, $this->createCountingPageTree($getTreeCalls));

        // Two different exclude strings, so only the page tree cache can prevent building the tree of page 5 twice
        $first = $this->subject->expandExcludeString('5+1');
        $second = $this->subject->expandExcludeString('5+1,3');

        self::assertSame([5, 50], array_values($first));
        self::assertSame([5, 50, 3], array_values($second));
        self::assertSame(1, $getTreeCalls);
    }

    #[Test]
    public function expandExcludeStringDoesNotMixSubpagesOfDifferentPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();
        $backendUser->method('getPagePermsClause')->willReturn('1=1');
        $backendUser->method('isInWebMount')->willReturn(1);
        $GLOBALS['BE_USER'] = $backendUser;

        // Page 3 has the subpage 6, page 5 has the subpages 7 and 8
        $both = $this->subject->expandExcludeString('3+1,5+1');
        $onlyPageFive = $this->subject->expandExcludeString('5+1,4');

        self::assertEqualsCanonicalizing([3, 6, 5, 7, 8], $both);
        self::assertEqualsCanonicalizing([5, 7, 8, 4], $onlyPageFive);
    }

    #[Test]
    public function getConfigurationFromDatabaseReturnsArray(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'maxCompileUrls' => 100,
        ];

        $urlService = GeneralUtility::makeInstance(UrlService::class);
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $configurationService = GeneralUtility::makeInstance(
            ConfigurationService::class,
            $urlService,
            $configurationRepository,
            GeneralUtility::makeInstance(GroupResolver::class),
            new ParameterExpander(),
            new ExcludeStringExpander(),
        );

        $configurations = $configurationService->getConfigurationFromDatabase(1, []);

        self::assertArrayHasKey('default', $configurations);
        self::assertArrayHasKey('Not hidden or deleted', $configurations);
    }

    private function createCountingPageTree(int &$getTreeCalls): PageTreeView
    {
        $tree = $this->createMock(PageTreeView::class);
        $tree->method('getTree')->willReturnCallback(
            static function (int $uid) use ($tree, &$getTreeCalls): int {
                $getTreeCalls++;
                $tree->tree = [[
                    'row' => [
                        'uid' => $uid * 10,
                    ],
                ]];
                return 1;
            }
        );
        return $tree;
    }
}
