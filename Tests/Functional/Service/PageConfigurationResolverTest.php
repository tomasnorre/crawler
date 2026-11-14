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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(PageConfigurationResolver::class)]
class PageConfigurationResolverTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;

    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private PageConfigurationResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupBackendRequest();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');

        $this->subject = new PageConfigurationResolver(
            GeneralUtility::makeInstance(PageService::class),
            GeneralUtility::makeInstance(ConfigurationService::class),
            GeneralUtility::makeInstance(ConfigurationRepository::class),
        );
    }

    #[Test]
    public function getConfigurationNamesForBranch(): void
    {
        $GLOBALS['BE_USER'] = $this->createStub(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->method('isAdmin')->willReturn(true);

        $configurationNames = $this->subject->getConfigurationNamesForBranch(5, 99);

        // sort is done as MySQL and SQLite doesn't sort the same way even though sorting is by "name ASC"
        sort($configurationNames);
        $expected = [
            'default',
            'Not hidden or deleted',
            'Not hidden or deleted - uid 5',
            'Not hidden or deleted - uid 6',
        ];
        sort($expected);

        self::assertSame($expected, $configurationNames);
    }
}
