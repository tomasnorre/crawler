<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Controller\CrawlerController;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

/**
 * Class CrawlerLibTest
 *
 * @package AOE\Crawler\Tests
 */
class CrawlerControllerTest extends UnitTestCase
{
    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        $this->crawlerController = $this->createPartialMock(
            CrawlerController::class,
            ['buildRequestHeaderArray', 'executeShellCommand', 'getFrontendBasePath']
        );
        $this->crawlerController->setLogger(new NullLogger());

        $configuration = [
            'sleepTime' => '1000',
            'sleepAfterFinish' => '10',
            'countInARun' => '100',
            'purgeQueueDays' => '14',
            'processLimit' => '1',
            'processMaxRunTime' => '300',
            'maxCompileUrls' => '10000',
            'processDebug' => '0',
            'processVerbose' => '0',
            'crawlHiddenPages' => '0',
            'phpPath' => '/usr/bin/php',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
    }

    /**
     * Resets the test environment after the test.
     */
    protected function tearDown(): void
    {
        unset($this->crawlerController);
    }

    /**
     * @test
     */
    public function setAndGet(): void
    {
        $accessMode = 'cli';
        $this->crawlerController->setAccessMode($accessMode);

        self::assertEquals(
            $accessMode,
            $this->crawlerController->getAccessMode()
        );
    }

    /**
     * @test
     *
     * @dataProvider setAndGetDisabledDataProvider
     */
    public function setAndGetDisabled(bool $disabled, bool $expected): void
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo') ?: 'FileNameIsForceIfTempNamReturnedFalse.txt';
        $this->crawlerController->setProcessFilename($filenameWithPath);
        $this->crawlerController->setDisabled($disabled);

        self::assertEquals(
            $expected,
            $this->crawlerController->getDisabled()
        );
    }

    /**
     * @test
     */
    public function setAndGetProcessFilename(): void
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo') ?: 'FileNameIsForceIfTempNamReturnedFalse.txt';
        $this->crawlerController->setProcessFilename($filenameWithPath);

        self::assertEquals(
            $filenameWithPath,
            $this->crawlerController->getProcessFilename()
        );
    }

    /**
     * @test
     *
     * @dataProvider drawURLs_PIfilterDataProvider
     */
    public function drawURLsPIfilter(string $piString, array $incomingProcInstructions, bool $expected): void
    {
        self::assertEquals(
            $expected,
            $this->crawlerController->drawURLs_PIfilter($piString, $incomingProcInstructions)
        );
    }

    /**
     * @test
     *
     * @dataProvider hasGroupAccessDataProvider
     */
    public function hasGroupAccess(string $groupList, string $accessList, bool $expected): void
    {
        self::assertEquals(
            $expected,
            $this->crawlerController->hasGroupAccess($groupList, $accessList)
        );
    }

    /**
     * @test
     *
     * @dataProvider getUrlsForPageRowDataProvider
     */
    public function getUrlsForPageRow(bool $checkIfPageSkipped, array $getUrlsForPages, array $pageRow, string $skipMessage, array $expected): void
    {
        /** @var MockObject|CrawlerController $crawlerController */
        $crawlerController = $this->createPartialMock(CrawlerController::class, ['checkIfPageShouldBeSkipped', 'getUrlsForPageId']);
        $crawlerController->expects($this->any())->method('checkIfPageShouldBeSkipped')->will($this->returnValue($checkIfPageSkipped));
        $crawlerController->expects($this->any())->method('getUrlsForPageId')->will($this->returnValue($getUrlsForPages));

        self::assertEquals(
            $expected,
            $crawlerController->getUrlsForPageRow($pageRow, $skipMessage)
        );
    }

    /**
     * @return array
     */
    public function getUrlsForPageRowDataProvider()
    {
        return [
            'Message equals false, returns Urls from getUrlsForPages()' => [
                'checkIfPageSkipped' => false,
                'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
                'pageRow' => ['uid' => 2001],
                '$skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
                'expected' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            ],
            'Message string not empty, returns empty array' => [
                'checkIfPageSkipped' => 'Because page is hidden',
                'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
                'pageRow' => ['uid' => 2001],
                '$skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
                'expected' => [],
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider compileUrlsDataProvider
     */
    public function compileUrls(array $paramArray, array $urls, array $expected): void
    {
        self::assertEquals(
            $expected,
            $this->crawlerController->compileUrls($paramArray, $urls)
        );
    }

    /**
     * @return array
     */
    public function compileUrlsDataProvider()
    {
        return [
            'Empty Params array' => [
                'paramArray' => [],
                'urls' => ['/home', '/search', '/about'],
                'expected' => ['/home', '/search', '/about'],
            ],
            'Empty Urls array' => [
                'paramArray' => ['pagination' => [1, 2, 3, 4]],
                'urls' => [],
                'expected' => [],
            ],
            'case' => [
                'paramArray' => ['pagination' => [1, 2, 3, 4]],
                'urls' => ['index.php?id=10', 'index.php?id=11'],
                'expected' => [
                    'index.php?id=10&pagination=1',
                    'index.php?id=10&pagination=2',
                    'index.php?id=10&pagination=3',
                    'index.php?id=10&pagination=4',
                    'index.php?id=11&pagination=1',
                    'index.php?id=11&pagination=2',
                    'index.php?id=11&pagination=3',
                    'index.php?id=11&pagination=4',
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider checkIfPageShouldBeSkippedDataProvider
     */
    public function checkIfPageShouldBeSkipped(array $extensionSetting, array $pageRow, array $excludeDoktype, array $pageVeto, string $expected): void
    {
        $this->crawlerController->setExtensionSettings($extensionSetting);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] = $pageVeto;

        self::assertEquals(
            $expected,
            $this->crawlerController->checkIfPageShouldBeSkipped($pageRow)
        );
    }

    /**
     * @test
     */
    public function cLIBuildProcessIdIsSetReturnsValue(): void
    {
        $processId = '12297a261b';
        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);
        $crawlerController->_set('processID', $processId);

        self::assertEquals(
            $processId,
            $crawlerController->_call('CLI_buildProcessId')
        );
    }

    /**
     * @test
     *
     * @param string $expected
     *
     * @dataProvider getConfigurationHasReturnsExpectedValueDataProvider
     */
    public function getConfigurationHasReturnsExpectedValue(array $configuration, $expected): void
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);

        self::assertEquals(
            $expected,
            $crawlerLib->_call('getConfigurationHash', $configuration)
        );
    }

    /**
     * @return array
     */
    public function getConfigurationHasReturnsExpectedValueDataProvider()
    {
        return [
            'Configuration with either paramExpanded nor URLs set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => '',
                    'URLs' => '',
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
            ],
            'Configuration with only paramExpanded set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important',
                    'URLs' => '',
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
            ],
            'Configuration with only URLS set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => '',
                    'URLs' => 'Value not important',
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
            ],
            'Configuration with both paramExpanded and URLS set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important',
                    'URLs' => 'Value not important',
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
            ],
            'Configuration with both paramExpanded and URLS set, will return same hash' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important, but different than test case before',
                    'URLs' => 'Value not important, but different than test case before',
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
            ],
        ];
    }

    /**
     * @return array
     */
    public function checkIfPageShouldBeSkippedDataProvider()
    {
        return [
            'Page of doktype 1 - Standand' => [
                'extensionSetting' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => false,
            ],
            'Extension Setting do not crawl hidden pages and page is hidden' => [
                'extensionSetting' => ['crawlHiddenPages' => false],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 1,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because page is hidden',
            ],
            'Page of doktype 3 - External Url' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 3,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page of doktype 4 - Shortcut' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 4,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page of doktype 155 - Custom' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 155,
                    'hidden' => 0,
                ],
                'excludeDoktype' => ['custom' => 155],
                'pageVeto' => [],
                'expected' => 'Doktype was excluded by "custom"',
            ],
            'Page of doktype 255 - Out of allowed range' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 255,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page veto exists' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnTrue'],
                'expected' => 'Veto from hook "veto-func"',
            ],
            'Page veto exists - string' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnString'],
                'expected' => 'Veto because of {"pageRow":{"doktype":1,"hidden":0}}',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getConfigurationKeysDataProvider()
    {
        return [
            'cliObject with no -conf' => [
                'config' => [(string) '-d' => 4, (string) '-o' => 'url'],
                'expected' => [],
            ],
            'cliObject with one -conf' => [
                'config' => [(string) '-d' => 4, (string) '-o' => 'url', (string) '-conf' => 'default'],
                'expected' => ['default'],
            ],
            'cliObject with two -conf' => [
                'config' => [(string) '-d' => 4, (string) '-o' => 'url', (string) '-conf' => 'default,news'],
                'expected' => ['default', 'news'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function setAndGetDisabledDataProvider()
    {
        return [
            'setDisabled with true param' => [
                'disabled' => true,
                'expected' => true,
            ],
            'setDisabled with false param' => [
                'disabled' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function drawURLs_PIfilterDataProvider()
    {
        return [
            'Not in list' => [
                'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_unknown_extension_instruction',
                ],
                'expected' => false,
            ],
            'In list' => [
                'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_indexedsearch_reindex',
                ],
                'expected' => true,
            ],
            'Twice in list' => [
                'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_indexedsearch_reindex',
                    'tx_indexedsearch_reindex',
                ],
                'expected' => true,
            ],
            'Empty incomingProcInstructions' => [
                'piString' => '',
                'incomingProcInstructions' => [],
                'expected' => true,
            ],
            'In list CAPITALIZED' => [
                'piString' => 'tx_indexedsearch_reindex,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'TX_INDEXEDSEARCH_REINDES',
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function hasGroupAccessDataProvider()
    {
        return [
            'Do not have access' => [
                'groupList' => '1,2,3',
                'accessList' => '4,5,6',
                'expected' => false,
            ],
            'Do have access' => [
                'groupList' => '1,2,3,4',
                'accessList' => '4,5,6',
                'expected' => true,
            ],
            'Access List empty' => [
                'groupList' => '1,2,3',
                'accessList' => '',
                'expected' => true,
            ],
        ];
    }
}
