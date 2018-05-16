<?php
namespace AOE\Crawler\Tests\Unit\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Command\QueueCommandLineController;
use AOE\Crawler\Controller\CrawlerController;
use Nimut\TestingFramework\TestCase\UnitTestCase;

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
     *
     * @return void
     */
    public function setUp()
    {
        $this->crawlerController = $this->getMock(
            CrawlerController::class,
            ['buildRequestHeaderArray', 'executeShellCommand', 'getFrontendBasePath'],
            [],
            '',
            false
        );
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';
    }

    /**
     * Resets the test environment after the test.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->crawlerController);
    }

    /**
     * @test
     */
    public function setAndGet()
    {
        $accessMode = 'cli';
        $this->crawlerController->setAccessMode($accessMode);

        $this->assertEquals(
            $accessMode,
            $this->crawlerController->getAccessMode()
        );
    }

    /**
     * @test
     *
     * @dataProvider setAndGetDisabledDataProvider
     */
    public function setAndGetDisabled($disabled, $expected)
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo');
        $this->crawlerController->setProcessFilename($filenameWithPath);

        if (null === $disabled) {
            $this->crawlerController->setDisabled();
        } else {
            $this->crawlerController->setDisabled($disabled);
        }
        $this->assertEquals(
            $expected,
            $this->crawlerController->getDisabled()
        );
    }

    /**
     * @test
     */
    public function setAndGetProcessFilename()
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo');
        $this->crawlerController->setProcessFilename($filenameWithPath);

        $this->assertEquals(
            $filenameWithPath,
            $this->crawlerController->getProcessFilename()
        );
    }

    /**
     * @test
     *
     * @dataProvider drawURLs_PIfilterDataProvider
     */
    public function drawURLs_PIfilter($piString, $incomingProcInstructions, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->crawlerController->drawURLs_PIfilter($piString, $incomingProcInstructions)
        );
    }

    /**
     * @test
     *
     * @param $groupList
     * @param $accessList
     * @param $expected
     *
     * @dataProvider hasGroupAccessDataProvider
     */
    public function hasGroupAccess($groupList, $accessList, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->crawlerController->hasGroupAccess($groupList, $accessList)
        );
    }

    /**
     * @test
     *
     * @param $inputQuery
     * @param $expected
     *
     * @dataProvider parseParamsDataProvider
     */
    public function parseParams($inputQuery, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->crawlerController->parseParams($inputQuery)
        );
    }

    /**
     * @test
     *
     * @param $checkIfPageSkipped
     * @param $getUrlsForPages
     * @param $pageRow
     * @param $skipMessage
     * @param $expected
     *
     * @dataProvider getUrlsForPageRowDataProvider
     */
    public function getUrlsForPageRow($checkIfPageSkipped, $getUrlsForPages, $pageRow, $skipMessage, $expected)
    {
        /** @var CrawlerController $crawlerController */
        $crawlerController = $this->getMock(CrawlerController::class, ['checkIfPageShouldBeSkipped', 'getUrlsForPageId'], [], '', false);
        $crawlerController->expects($this->any())->method('checkIfPageShouldBeSkipped')->will($this->returnValue($checkIfPageSkipped));
        $crawlerController->expects($this->any())->method('getUrlsForPageId')->will($this->returnValue($getUrlsForPages));

        $this->assertEquals(
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
                'expected' => ['index.php?q=search&page=1', 'index.php?q=search&page=2']
            ],
            'Message string not empty, returns empty array' => [
                'checkIfPageSkipped' => 'Because page is hidden',
                'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
                'pageRow' => ['uid' => 2001],
                '$skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
                'expected' => []
            ],
        ];
    }

    /**
     * @test
     *
     * @param $paramArray
     * @param $urls
     * @param $expected
     *
     * @dataProvider compileUrlsDataProvider
     */
    public function compileUrls($paramArray, $urls, $expected)
    {
        $this->assertEquals(
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
                'expected' => ['/home', '/search', '/about']
            ],
            'Empty Urls array' => [
                'paramArray' => ['pagination' => [1, 2, 3, 4]],
                'urls' => [],
                'expected' => []
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
                ]
            ]
        ];
    }

    /**
     * Tests whether the makeDirectRequest feature works properly.
     *
     * @test
     */
    public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithoutDefinedBasePath()
    {
        $this->crawlerController->setExtensionSettings([
            'makeDirectRequests' => 1,
            'frontendBasePath' => '',
            'phpPath' => 'PHPPATH',
        ]);

        $testUrl = 'http://localhost/' . uniqid();
        $testHeader = 'X-Test: ' . uniqid();
        $testHeaderArray = [$testHeader];
        $testCrawlerId = 13;
        $testContent = uniqid('Content');
        $frontendBasePath = '/';

        $expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
                           escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php') . ' ' .
                           escapeshellarg($frontendBasePath) . ' ' .
                           escapeshellarg($testUrl) . ' ' .
                           escapeshellarg(base64_encode(serialize($testHeaderArray)));

        $this->crawlerController->expects($this->once())->method('buildRequestHeaderArray')
                             ->will($this->returnValue($testHeaderArray));
        $this->crawlerController->expects($this->once())->method('executeShellCommand')
                             ->with($expectedCommand)->will($this->returnValue($testContent));
        $this->crawlerController->expects($this->once())->method('getFrontendBasePath')
                             ->will($this->returnValue($frontendBasePath));

        $result = $this->crawlerController->requestUrl($testUrl, $testCrawlerId);

        $this->assertEquals($testHeader . str_repeat("\r\n", 2), $result['request']);
        $this->assertEquals($testContent, $result['content']);
    }

    /**
     * Tests whether the makeDirectRequest feature works properly.
     *
     * @test
     */
    public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithDefinedBasePath()
    {
        $this->crawlerController->setExtensionSettings([
            'makeDirectRequests' => 1,
            'frontendBasePath' => '/cms/',
            'phpPath' => 'PHPPATH',
        ]);

        $testUrl = 'http://localhost/' . uniqid();
        $testHeader = 'X-Test: ' . uniqid();
        $testHeaderArray = [$testHeader];
        $testCrawlerId = 13;
        $testContent = uniqid('Content');
        $frontendBasePath = '/cms/';

        $expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
                           escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php') . ' ' .
                           escapeshellarg($frontendBasePath) . ' ' .
                           escapeshellarg($testUrl) . ' ' .
                           escapeshellarg(base64_encode(serialize($testHeaderArray)));

        $this->crawlerController->expects($this->once())->method('buildRequestHeaderArray')
                             ->will($this->returnValue($testHeaderArray));
        $this->crawlerController->expects($this->once())->method('executeShellCommand')
                             ->with($expectedCommand)->will($this->returnValue($testContent));
        $this->crawlerController->expects($this->once())->method('getFrontendBasePath')
                             ->will($this->returnValue($frontendBasePath));

        $result = $this->crawlerController->requestUrl($testUrl, $testCrawlerId);

        $this->assertEquals($testHeader . str_repeat("\r\n", 2), $result['request']);
        $this->assertEquals($testContent, $result['content']);
    }

    /**
     * @test
     *
     * @param array $url
     * @param string $crawlerId
     * @param array $expected
     *
     * @dataProvider buildRequestHeaderArrayDataProvider
     */
    public function buildRequestHeaderArray($url, $crawlerId, $expected)
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);

        $this->assertEquals(
            $expected,
            $crawlerLib->_call('buildRequestHeaderArray', $url, $crawlerId)
        );
    }

    /**
     * @test
     *
     * @param $headers
     * @param $user
     * @param $pass
     * @param $expected
     *
     * @dataProvider getRequestUrlFrom302HeaderDataProvider
     */
    public function getRequestUrlFrom302Header($headers, $user, $pass, $expected)
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);

        $this->assertEquals(
            $expected,
            $crawlerLib->_call('getRequestUrlFrom302Header', $headers, $user, $pass)
        );
    }
    
    /**
     * @test
     *
     * @param $crawlerConfiguration
     * @param $pageConfiguration
     * @param $expected
     *
     * @dataProvider isCrawlingProtocolHttpsDataProvider
     */
    public function isCrawlingProtocolHttps($crawlerConfiguration, $pageConfiguration, $expected)
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);

        $this->assertEquals(
            $expected,
            $crawlerLib->_call('isCrawlingProtocolHttps', $crawlerConfiguration, $pageConfiguration, $expected)
        );
    }

    /**
     * @return array
     */
    public function isCrawlingProtocolHttpsDataProvider()
    {
        return [
            'Crawler configuration is -1 (Force http)' => [
                'crawlerConfiguration' => -1,
                'pageConfiguration' => true,
                'expected' => false
            ],
            'Crawler configuration is 0 (Respect Page), Page is true' => [
                'crawlerConfiguration' => 0,
                'pageConfiguration' => true,
                'expected' => true
            ],
            'Crawler configuration is 0 (Respect Page), Page is false' => [
                'crawlerConfiguration' => 0,
                'pageConfiguration' => false,
                'expected' => false
            ],
            'Crawler configuration is 1 (Force https)' => [
                'crawlerConfiguration' => 1,
                'pageConfiguration' => false,
                'expected' => true
            ],
            'Crawler configuration is not expected value -1, 0 or 1' => [
                'crawlerConfiguration' => 32,
                'pageConfiguration' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @test
     *
     * @param $config
     * @param $expected
     *
     * @dataProvider getConfigurationKeysDataProvider
     */
    public function getConfigurationKeys($config, $expected)
    {
        // FIXME
        $this->markTestSkipped('Skipped as the cli_getArgIndex is reset $config when parsing...');

        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);
        $_SERVER['argv'] = $config;
        $cliObject = new QueueCommandLineController();

        $this->assertEquals(
            $expected,
            $crawlerController->_callRef('getConfigurationKeys', $cliObject)
        );
    }

    /**
     * @test
     *
     * @param $extensionSetting
     * @param $pageRow
     * @param $excludeDoktype
     * @param $expected
     *
     * @dataProvider checkIfPageShouldBeSkippedDataProvider
     */
    public function checkIfPageShouldBeSkipped($extensionSetting, $pageRow, $excludeDoktype, $expected)
    {
        $this->crawlerController->setExtensionSettings($extensionSetting);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;

        $this->assertEquals(
            $expected,
            $this->crawlerController->checkIfPageShouldBeSkipped($pageRow)
        );
    }

    /**
     * @test
     */
    public function CLI_buildProcessIdNotSet()
    {
        $microtime = '1481397820.81820011138916015625';
        $expectedMd5Value = '95297a261b';

        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['microtime'], [], '', false);
        $crawlerController->expects($this->once())->method('microtime')->will($this->returnValue($microtime));

        $this->assertEquals(
            $expectedMd5Value,
            $crawlerController->_call('CLI_buildProcessId')
        );
    }

    /**
     * @test
     */
    public function CLI_buildProcessIdIsSetReturnsValue()
    {
        $processId = '12297a261b';
        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);
        $crawlerController->_set('processID', $processId);

        $this->assertEquals(
            $processId,
            $crawlerController->_call('CLI_buildProcessId')
        );
    }

    /**
     * @test
     *
     * @param array $configuration
     * @param string $expected
     *
     * @dataProvider getConfigurationHasReturnsExpectedValueDataProvider
     */
    public function getConfigurationHasReturnsExpectedValue(array $configuration, $expected)
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, ['dummy'], [], '', false);

        $this->assertEquals(
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
                    'URLs' => ''
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22'
            ],
            'Configuration with only paramExpanded set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important',
                    'URLs' => ''
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22'
            ],
            'Configuration with only URLS set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => '',
                    'URLs' => 'Value not important'
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22'
            ],
            'Configuration with both paramExpanded and URLS set' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important',
                    'URLs' => 'Value not important'
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22'
            ],
            'Configuration with both paramExpanded and URLS set, will return same hash' => [
                'configuration' => [
                    'testKey' => 'testValue',
                    'paramExpanded' => 'Value not important, but different than test case before',
                    'URLs' => 'Value not important, but different than test case before'
                ],
                'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22'
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
                    'hidden' => 0
                ],
                'excludeDoktype' => [],
                'expected' => false
            ],
            'Extension Setting do not crawl hidden pages and page is hidden' => [
                'extensionSetting' => ['crawlHiddenPages' => false],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 1
                ],
                'excludeDoktype' => [],
                'expected' => 'Because page is hidden',
            ],
            'Page of doktype 3 - External Url' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 3,
                    'hidden' => 0
                ],
                'excludeDoktype' => [],
                'expected' => 'Because doktype is not allowed'
            ],
            'Page of doktype 4 - Shortcut' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 4,
                    'hidden' => 0
                ],
                'excludeDoktype' => [],
                'expected' => 'Because doktype is not allowed'
            ],
            'Page of doktype 155 - Custom' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 155,
                    'hidden' => 0
                ],
                'excludeDoktype' => ['custom' => 155],
                'expected' => 'Doktype was excluded by "custom"'
            ],
            'Page of doktype 255 - Out of allowed range' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 255,
                    'hidden' => 0
                ],
                'excludeDoktype' => [],
                'expected' => 'Because doktype is not allowed'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getConfigurationKeysDataProvider()
    {
        return [
            'cliObject with no -conf' => [
                'config' => [(string)'-d' => 4, (string)'-o' => 'url'],
                'expected' => []
            ],
            'cliObject with one -conf' => [
                'config' => [(string)'-d' => 4, (string)'-o' => 'url', (string)'-conf' => 'default'],
                'expected' => ['default']
            ],
            'cliObject with two -conf' => [
                'config' => [(string)'-d' => 4, (string)'-o' => 'url', (string)'-conf' => 'default,news'],
                'expected' => ['default', 'news']
            ]
        ];
    }

    /**
     * @return array
     */
    public function getRequestUrlFrom302HeaderDataProvider()
    {
        return [
            'Header is now array' => [
                'headers' => 'no-array',
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 301 Moved - no Location' => [
                'headers' => ['HTTP/1.1 301 Moved'],
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 302 Found - no Location' => [
                'headers' => ['HTTP/1.1 302 Found'],
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 302 Moved - no Location' => [
                'headers' => ['HTTP/1.1 302 Moved'],
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 304 Not Modified - no Location' => [
                'headers' => ['HTTP/1.1 304 Not Modified'],
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 301 Moved - with Location' => [
                'headers' => [
                    'HTTP/1.1 301 Moved',
                    'Location: http://localhost/new-url'
                ],
                'user' => '',
                'pass' => '',
                'expected' => 'http://localhost/new-url'
            ],
            'Header 302 Found - with Location' => [
                'headers' => [
                    'HTTP/1.1 302 Found',
                    'Location: http://localhost/new-url'
                ],
                'user' => '',
                'pass' => '',
                'expected' => 'http://localhost/new-url'
            ],
            'Header 302 Moved - with Location' => [
                'headers' => [
                    'HTTP/1.1 302 Moved',
                    'Location: http://localhost/new-url'
                ],
                'user' => '',
                'pass' => '',
                'expected' => 'http://localhost/new-url'
            ],
            'Header 304 Not Modified - with Location' => [
                'headers' => [
                    'HTTP/1.1 304 Not Modified',
                    'Location: http://localhost/new-url'
                ]
                ,
                'user' => '',
                'pass' => '',
                'expected' => false
            ],
            'Header 302 Moved - with Location and User credentials' => [
                'headers' => [
                    'HTTP/1.1 302 Moved',
                    'Location: http://localhost/new-url'
                ],
                'user' => 'username',
                'pass' => 'password',
                'expected' => 'http://username:password@localhost/new-url'
            ],
            'Header 302 Moved - with Location and User credentials (https)' => [
                'headers' => [
                    'HTTP/1.1 302 Moved',
                    'Location: https://localhost/new-url'
                ],
                'user' => 'username',
                'pass' => 'password',
                'expected' => 'https://username:password@localhost/new-url'
            ],
            'Header 302 Moved - with non-conform location' => [
                'headers' => [
                    'HTTP/1.1 302 Moved',
                    'Location: http://:80'
                ],
                'user' => 'username',
                'pass' => 'password',
                'expected' => false
            ],
        ];
    }

    /**
     * @return array
     */
    public function buildRequestHeaderArrayDataProvider()
    {
        return [
            'Request without query, ADMCMD_previewWS nor User Credentials' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => '',
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/ HTTP/1.0',
                    'Host: http://localhost',
                    'Connection: close',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
            'Request with simple query, but without ADMCMD_previewWS and User Credentials' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => 'q=search',
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/?q=search HTTP/1.0',
                    'Host: http://localhost',
                    'Connection: close',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
            'Request with \'complex\' query, but without ADMCMD_previewWS and User credentials' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => 'q=search&page=2',
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/?q=search&page=2 HTTP/1.0',
                    'Host: http://localhost',
                    'Connection: close',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
            'Request without User credentials, but with ADMCMD_previewWS' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => 'ADMCMD_previewWS=345',
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/?ADMCMD_previewWS=345 HTTP/1.0',
                    'Host: http://localhost',
                    'Cookie: $Version="1"; be_typo_user="1"; $Path=/',
                    'Connection: close',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
            'Request without query and ADMCMD_previewWS, but with User credentials' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => '',
                    'user' => 'username',
                    'pass' => 'password'
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/ HTTP/1.0',
                    'Host: http://localhost',
                    'Connection: close',
                    'Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
            'Request with query, ADMCMD_previewWS and User credentials' => [
                'url' => [
                    'host' => 'http://localhost',
                    'path' => '/home/',
                    'query' => 'q=search&ADMCMD_previewWS=234',
                    'user' => 'username',
                    'pass' => 'password'
                ],
                'crawlerId' => 'qwerty',
                'expected' => [
                    'GET /home/?q=search&ADMCMD_previewWS=234 HTTP/1.0',
                    'Host: http://localhost',
                    'Cookie: $Version="1"; be_typo_user="1"; $Path=/',
                    'Connection: close',
                    'Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=',
                    'X-T3crawler: qwerty',
                    'User-Agent: TYPO3 crawler'
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public function setAndGetDisabledDataProvider()
    {
        return [
            'setDisabled with no param' => [
                'disabled' => null,
                'expected' => true
            ],
            'setDisabled with true param' => [
                'disabled' => true,
                'expected' => true
            ],
            'setDisabled with false param' => [
                'disabled' => false,
                'expected' => false
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
                'piString' => 'tx_indexedsearch_reindex,tx_realurl_rebuild,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_unknown_extension_instruction'
                ],
                'expected' => false
            ],
            'In list' => [
                'piString' => 'tx_indexedsearch_reindex,tx_realurl_rebuild,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_indexedsearch_reindex',
                ],
                'expected' => true
            ],
            'Twice in list' => [
                'piString' => 'tx_indexedsearch_reindex,tx_realurl_rebuild,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'tx_realurl_rebuild',
                    'tx_realurl_rebuild'
                ],
                'expected' => true
            ],
            'Empty incomingProcInstructions' => [
                'piString' => '',
                'incomingProcInstructions' => [],
                'expected' => true
            ],
            'In list CAPITALIZED' => [
                'piString' => 'tx_indexedsearch_reindex,tx_realurl_rebuild,tx_esetcache_clean_main',
                'incomingProcInstructions' => [
                    'TX_REALURL_REBUILD'
                ],
                'expected' => false
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
                'expected' => false
            ],
            'Do have access' => [
                'groupList' => '1,2,3,4',
                'accessList' => '4,5,6',
                'expected' => true
            ],
            'Access List empty' => [
                'groupList' => '1,2,3',
                'accessList' => '',
                'expected' => true
            ]
        ];
    }

    /**
     * @return array
     */
    public function parseParamsDataProvider()
    {
        return [
            'Empty query string' => [
                'queryString' => '',
                'expected' => []
            ],
            'Query string with one variable' => [
                'queryString' => 'q=search',
                'expected' => ['q' => 'search']
            ],
            'Query string with two variables' => [
                'queryString' => 'q=search&page=3',
                'expected' => [
                    'q' => 'search',
                    'page' => 3
                ]
            ]
        ];
    }
}
