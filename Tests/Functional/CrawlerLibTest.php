<?php
namespace AOE\Crawler\Tests\Functional;

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

use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Class CrawlerLibTest
 *
 * @package AOE\Crawler\Tests\Functional
 */
class CrawlerLibTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = array('cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager');

    /**
     * @var array
     */
    protected $testExtensionsToLoad = array('typo3conf/ext/crawler');

    /**
     * @var \tx_crawler_lib
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(dirname(__FILE__) . '/Fixtures/sys_domain.xml');
        $this->subject = $this->getAccessibleMock('\tx_crawler_lib', ['dummy']);
    }

    /**
     * @test
     *
     * @param $baseUrl
     * @param $sysDomainUid
     * @param $expected
     *
     * @dataProvider getBaseUrlForConfigurationRecordDataProvider
     */
    public function getBaseUrlForConfigurationRecord($baseUrl, $sysDomainUid, $expected)
    {
        $this->assertEquals(
           $expected,
            $this->subject->_call('getBaseUrlForConfigurationRecord', $baseUrl, $sysDomainUid)
        );
    }

    /**
     * @test
     */
    public function cleanUpOldQueueEntries()
    {
        $this->markTestSkipped('This fails with PHP7 & TYPO3 7.6');

        $this->importDataSet(dirname(__FILE__) . '/Fixtures/tx_crawler_queue.xml');
        $queryRepository = new \tx_crawler_domain_queue_repository();


        $recordsFromFixture = 9;
        $expectedRemainingRecords = 2;
        // Add records to queue repository to ensure we always have records,
        // that will not be deleted with the cleanUpOldQueueEntries-function
        for ($i=0; $i < $expectedRemainingRecords; $i++) {
            $this->getDatabaseConnection()->exec_INSERTquery(
                'tx_crawler_queue',
                [
                    'exec_time' => time() + (7 * 24 * 60 * 60),
                    'scheduled' => time() + (7 * 24 * 60 * 60)
                ]
            );
        }

        // Check total entries before cleanup
        $this->assertEquals(
            $recordsFromFixture + $expectedRemainingRecords ,
            $queryRepository->countAll()
        );

        $this->subject->_call('cleanUpOldQueueEntries');

        // Check total entries after cleanup
        $this->assertEquals(
            $expectedRemainingRecords,
            $queryRepository->countAll()
        );
    }

    public function getBaseUrlForConfigurationRecordDataProvider()
    {
        return [
            'With existing sys_domain' => [
                'baseUrl' => 'www.baseurl-domain.tld',
                'sysDomainUid' => 1,
                'expected' => 'http://www.domain-one.tld'
            ],
            'Without exting sys_domain' => [
                'baseUrl' => 'www.baseurl-domain.tld',
                'sysDomainUid' => 2000,
                'expected' => 'www.baseurl-domain.tld'
            ],
            'With sys_domain uid with negative value' => [
                'baseUrl' => 'www.baseurl-domain.tld',
                'sysDomainUid' => -1,
                'expected' => 'www.baseurl-domain.tld'
            ]
        ];
    }
}