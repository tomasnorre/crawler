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
use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ConfigurationServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private ConfigurationService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->createPartialMock(ConfigurationService::class, []);
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
            $configurationRepository
        );

        $configurations = $configurationService->getConfigurationFromDatabase(1, []);

        self::assertArrayHasKey('default', $configurations);
        self::assertArrayHasKey('Not hidden or deleted', $configurations);
    }
}
