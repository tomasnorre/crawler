<?php
namespace AOE\Crawler\Tests\Functional\Domain\Repository;

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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Model\ProcessCollection;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class ProcessRepositoryTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    //protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var ProcessRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     *
     */
    public function setUp()
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';
        $this->importDataSet(dirname(__FILE__) . '/../../Fixtures/tx_crawler_process.xml');
        $this->subject = $objectManager->get(ProcessRepository::class);
    }

    /**
     * @test
     *
     * @param $orderField
     * @param $orderDirection
     * @param $itemCount
     * @param $offset
     * @param $where
     * @param $expected
     *
     * @dataProvider findAllDataProvider
     */
    public function findAll($orderField, $orderDirection, $itemCount, $offset, $where, $expected)
    {
        $actual = $this->subject->findAll($orderField, $orderDirection, $itemCount, $offset, $where);

        $this->assertEquals(
            $expected,
            $actual->getProcessIds()
        );
    }

    /**
     * @test
     */
    public function removeByProcessId()
    {
        $this->assertInstanceOf(
            ProcessCollection::class,
            $this->subject->findAll('', '', '', '', 'process_id = 1002')
        );

        $this->assertEquals(
            5,
            $this->subject->findAll()->count()
        );

        $this->subject->removeByProcessId(1002);

        $this->assertEquals(
            4,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @return array
     */
    public function findAllDataProvider()
    {
        return [
            'No Values set, all defaults will be used' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => [1004,1003,1002, 1001, 1000]
            ],
            'OrderField is set, rest of fields will be using default values' => [
                'orderField' => 'ttl',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => [1001, 1002, 1003, 1004, 1000]
            ],
            'OrderDirection is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => 'ASC',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => [1000, 1001, 1002, 1003, 1004]
            ],
            'ItemCount is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '2',
                'offset' => '',
                'where' => '',
                'expected' => [1004, 1003]
            ],
            'Offset is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '1',
                'where' => '',
                'expected' => [1003, 1002, 1001, 1000]
            ],
            'where is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => 'ttl < 20',
                'expected' => [1000]
            ],
            'All fields are set' => [
                'orderField' => 'process_id',
                'oderDirection' => 'ASC',
                'itemCount' => '1',
                'offset' => '1',
                'where' => 'process_id > 1000',
                'expected' => [1002]
            ],
        ];
    }

    /**
     * @test
     */
    public function countActive()
    {
        $this->assertEquals(
            3,
            $this->subject->countActive()
        );
    }

    /**
     * @test
     */
    public function countNotTimeouted()
    {
        $this->assertEquals(
            2,
            $this->subject->countNotTimeouted(11)
        );
    }

    /**
     * @test
     */
    public function countAll()
    {
        $this->assertEquals(
            5,
            $this->subject->countAll()
        );
    }

    /**
     * @test
     */
    public function getActiveProcessesOlderThanOneOHour()
    {
        $expected = [
            ['process_id' => '1000', 'system_process_id' => 0],
            ['process_id' => '1001', 'system_process_id' => 0],
            ['process_id' => '1002', 'system_process_id' => 0]
        ];

        $this->assertSame(
            $expected,
            $this->subject->getActiveProcessesOlderThanOneHour()
        );
    }

    /**
     * @test
     */
    public function getActiveOrphanProcesses()
    {
        $expected = [
            ['process_id' => '1000', 'system_process_id' => 0],
            ['process_id' => '1001', 'system_process_id' => 0],
            ['process_id' => '1002', 'system_process_id' => 0]
        ];

        $this->assertSame(
            $expected,
            $this->subject->getActiveOrphanProcesses()
        );
    }
}
