<?php
namespace AOE\Crawler\Tests\Unit;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class CrawlerLibTest
 *
 * @package AOE\Crawler\Tests
 */
class CrawlerLibTest extends UnitTestCase
{
    /**
     * @var \tx_crawler_lib
     */
    protected $crawlerLibrary;

    /**
     * Creates the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        $this->crawlerLibrary = $this->getMock(
            '\tx_crawler_lib',
            array('buildRequestHeaderArray', 'executeShellCommand', 'getFrontendBasePath'),
            array(),
            '',
            false
        );
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:20:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:2:"20";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"9";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"1";s:7:"phpPath";s:16:"/usr/bin/php5 -q";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"1";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";s:21:"PageUidRootTypoScript";s:1:"1";}';
    }

    /**
     * Resets the test environment after the test.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->crawlerLibrary);
    }

    /**
     * @test
     */
    public function setAndGet()
    {
        $accessMode = 'cli';
        $this->crawlerLibrary->setAccessMode($accessMode);

        $this->assertEquals(
            $accessMode,
            $this->crawlerLibrary->getAccessMode()
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
        $this->crawlerLibrary->setProcessFilename($filenameWithPath);

        if (null === $disabled) {
            $this->crawlerLibrary->setDisabled();
        } else {
            $this->crawlerLibrary->setDisabled($disabled);
        }
        $this->assertEquals(
            $expected,
            $this->crawlerLibrary->getDisabled()
        );
    }

    /**
     * @test
     */
    public function setAndGetProcessFilename()
    {
        $filenameWithPath = tempnam('/tmp', 'test_foo');
        $this->crawlerLibrary->setProcessFilename($filenameWithPath);

        $this->assertEquals(
            $filenameWithPath,
            $this->crawlerLibrary->getProcessFilename()
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
            $this->crawlerLibrary->drawURLs_PIfilter($piString, $incomingProcInstructions)
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
            $this->crawlerLibrary->hasGroupAccess($groupList, $accessList)
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
            $this->crawlerLibrary->parseParams($inputQuery)
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
        /** @var \tx_crawler_lib $crawlerLibrary */
        $crawlerLibrary = $this->getMock('\tx_crawler_lib', ['checkIfPageShouldBeSkipped', 'getUrlsForPageId']);
        $crawlerLibrary->expects($this->any())->method('checkIfPageShouldBeSkipped')->will($this->returnValue($checkIfPageSkipped));
        $crawlerLibrary->expects($this->any())->method('getUrlsForPageId')->will($this->returnValue($getUrlsForPages));

        $this->assertEquals(
            $expected,
            $crawlerLibrary->getUrlsForPageRow($pageRow, $skipMessage)
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
            $this->crawlerLibrary->compileUrls($paramArray, $urls)
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
        $this->crawlerLibrary->setExtensionSettings(array(
            'makeDirectRequests' => 1,
            'frontendBasePath' => '',
            'phpPath' => 'PHPPATH',
        ));

        $testUrl = 'http://localhost/' . uniqid();
        $testHeader = 'X-Test: ' . uniqid();
        $testHeaderArray = array($testHeader);
        $testCrawlerId = 13;
        $testContent = uniqid('Content');
        $frontendBasePath = '/';

        $expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
            escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php') . ' ' .
            escapeshellarg($frontendBasePath) . ' ' .
            escapeshellarg($testUrl) . ' ' .
            escapeshellarg(base64_encode(serialize($testHeaderArray)));

        $this->crawlerLibrary->expects($this->once())->method('buildRequestHeaderArray')
            ->will($this->returnValue($testHeaderArray));
        $this->crawlerLibrary->expects($this->once())->method('executeShellCommand')
            ->with($expectedCommand)->will($this->returnValue($testContent));
        $this->crawlerLibrary->expects($this->once())->method('getFrontendBasePath')
            ->will($this->returnValue($frontendBasePath));

        $result = $this->crawlerLibrary->requestUrl($testUrl, $testCrawlerId);

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
        $this->crawlerLibrary->setExtensionSettings(array(
            'makeDirectRequests' => 1,
            'frontendBasePath' => '/cms/',
            'phpPath' => 'PHPPATH',
        ));

        $testUrl = 'http://localhost/' . uniqid();
        $testHeader = 'X-Test: ' . uniqid();
        $testHeaderArray = array($testHeader);
        $testCrawlerId = 13;
        $testContent = uniqid('Content');
        $frontendBasePath = '/cms/';

        $expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
            escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php') . ' ' .
            escapeshellarg($frontendBasePath) . ' ' .
            escapeshellarg($testUrl) . ' ' .
            escapeshellarg(base64_encode(serialize($testHeaderArray)));

        $this->crawlerLibrary->expects($this->once())->method('buildRequestHeaderArray')
            ->will($this->returnValue($testHeaderArray));
        $this->crawlerLibrary->expects($this->once())->method('executeShellCommand')
            ->with($expectedCommand)->will($this->returnValue($testContent));
        $this->crawlerLibrary->expects($this->once())->method('getFrontendBasePath')
            ->will($this->returnValue($frontendBasePath));

        $result = $this->crawlerLibrary->requestUrl($testUrl, $testCrawlerId);

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
        $crawlerLib = $this->getAccessibleMock('\tx_crawler_lib', ['dummy']);

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
        $crawlerLib = $this->getAccessibleMock('\tx_crawler_lib', ['dummy']);

        $this->assertEquals(
            $expected,
            $crawlerLib->_call('getRequestUrlFrom302Header', $headers, $user, $pass)
        );
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

        $crawlerLibrary = $this->getAccessibleMock('\tx_crawler_lib', ['dummy']);
        $cliObject = new \tx_crawler_cli_im();
        $cliObject->cli_setArguments($config);


        $this->assertEquals(
            $expected,
            $crawlerLibrary->_callRef('getConfigurationKeys', $cliObject)
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
        $this->crawlerLibrary->setExtensionSettings($extensionSetting);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;

        $this->assertEquals(
            $expected,
            $this->crawlerLibrary->checkIfPageShouldBeSkipped($pageRow)
        );
    }

    /**
     * @test
     */
    public function CLI_buildProcessIdNotSet()
    {
        $microtime = '1481397820.81820011138916015625';
        $expectedMd5Value = '95297a261b';

        $crawlerLibrary = $this->getAccessibleMock('tx_crawler_lib', ['microtime']);
        $crawlerLibrary->expects($this->once())->method('microtime')->will($this->returnValue($microtime));

        $this->assertEquals(
            $expectedMd5Value,
            $crawlerLibrary->_call('CLI_buildProcessId')
        );
    }

    /**
     * @test
     */
    public function CLI_buildProcessIdIsSetReturnsValue()
    {
        $processId = '12297a261b';
        $crawlerLibrary = $this->getAccessibleMock('tx_crawler_lib', ['dummy']);
        $crawlerLibrary->_set('processID', $processId);

        $this->assertEquals(
            $processId,
            $crawlerLibrary->_call('CLI_buildProcessId')
        );
    }

    /**
     * @test
     *
     * @dataProvider getFrontendBasePathDataProvider
     */
    public function getFrontendBasePath($frontendBasePath, $absRefPrefix, $TYPO3_cliMode, $expected)
    {
        /** @var \tx_crawler_lib $crawlerLibrary */
        $crawlerLibrary = $this->getAccessibleMock('\tx_crawler_lib', ['dummy']);

        // Setting up
        if (!empty($frontendBasePath)) {
            $crawlerLibrary->setExtensionSettings(['frontendBasePath' => $frontendBasePath]);
        }

        if (!empty($absRefPrefix)) {
            /** @var TypoScriptFrontendController $GLOBALS['TSFE'] */
            $GLOBALS['TSFE'] = $this->getMock('\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', ['dummy'], [], '', false);
            $GLOBALS['TSFE']->setAbsRefPrefix($absRefPrefix);
        }

        if (!empty($TYPO3_cliMode)) {
            define('TYPO3_cliMode', 'TYPO3_cliMode');
            define('TYPO3_SITE_PATH', '/var/www/htdocs/');
        }

        $this->assertEquals(
            $expected,
            $crawlerLibrary->_call('getFrontendBasePath')
        );
    }

    /**
     * @test
     */
    public function sendDirectRequestReturnsArray()
    {
        $url = 'http://test.domain.tld/index.php?id=123';
        $crawlerId = 'qwerty';

        $content = 'content-string';
        $requestHeaders = ['string' => 'value'];

        $expectedArray = [
            'request' => implode("\r\n", $requestHeaders) . "\r\n\r\n",
            'headers' => '',
            'content' => $content
        ];

        $crawlerLibrary = $this->getAccessibleMock('\tx_crawler_lib', ['executeShellCommand', 'buildRequestHeaderArray'], [], '', false);
        $crawlerLibrary->expects($this->once())->method('executeShellCommand')->will($this->returnValue($content));
        $crawlerLibrary->expects($this->once())->method('buildRequestHeaderArray')->will($this->returnValue($requestHeaders));

        $this->assertEquals(
            $expectedArray,
            $crawlerLibrary->_call('sendDirectRequest', $url, $crawlerId)
        );

    }

    /**
     * @return array
     */
    public function getFrontendBasePathDataProvider()
    {
        return [
            'No Settings configured' => [
                'frontendBasePath' => '',
                'absRefPrefix' => '',
                'TYPO3_cliMode' => '',
                'expected' => '/'
            ],
            'Setting frontend basePath' => [
                'frontendBasePath' => '/cms/',
                'absRefPrefix' => '',
                'TYPO3_cliMode' => '',
                'expected' => '/cms/'
            ],
            /*'Setting absRefPrefix :: FIXME' => [
                'frontendBasePath' => '',
                'absRefPrefix' => '/absRefPrefix/',
                'TYPO3_cliMode' => '',
                'expected' => '/sd/'
            ],
            'Setting TYPO3_cliMode :: FIXME' => [
                'frontendBasePath' => '',
                'absRefPrefix' => '',
                'TYPO3_cliMode' => true,
                'expected' => '/SD'
            ]*/
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
                'config' => [(string)'-d' => 4, (string)'-o' => 'url', (string)'-conf' =>  'default'],
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
