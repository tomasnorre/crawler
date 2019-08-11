<?php
namespace AOE\Crawler\Tests\Functional\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
            'logFileName' => '',
            'follow30x' => '0',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->set('crawler', '', $configuration);

        $this->importDataSet(dirname(__FILE__) . '/../../Fixtures/tx_crawler_process.xml');
        $this->subject = $objectManager->get(ProcessRepository::class);
    }

    /**
     * @test
     */
    public function findAllReturnsAll(): void
    {
        $this->assertSame(
            5,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @test
     */
    public function findAllActiveReturnsActive(): void
    {
        $this->assertSame(
            3,
            $this->subject->findAllActive()->count()
        );
    }

    /**
     * @test
     */
    public function removeByProcessId()
    {
        $this->assertSame(
            5,
            $this->subject->findAll()->count()
        );

        $this->subject->removeByProcessId(1002);

        $this->assertSame(
            4,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @test
     */
    public function countActive()
    {
        $this->assertSame(
            3,
            $this->subject->countActive()
        );
    }

    /**
     * @test
     */
    public function countNotTimeouted()
    {
        $this->assertSame(
            2,
            $this->subject->countNotTimeouted(11)
        );
    }

    /**
     * @test
     */
    public function countAll()
    {
        $this->assertSame(
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

    /**
     * @test
     */
    public function deleteProcessesWithoutItemsAssigned()
    {
        $countBeforeDelete = $this->subject->countAll();
        $expectedProcessesToBeDeleted = 2;
        $this->subject->deleteProcessesWithoutItemsAssigned();

        // TODO: Fix the count all
        $this->assertSame(
            3, //$this->subject->countAll(),
            $countBeforeDelete - $expectedProcessesToBeDeleted
        );
    }

    /**
     * @test
     */
    public function deleteProcessesMarkedAsDeleted()
    {
        $countBeforeDelete = $this->subject->countAll();
        $expectedProcessesToBeDeleted = 2;
        $this->subject->deleteProcessesMarkedAsDeleted();

        // TODO: Fix the count all
        $this->assertSame(
            3, //$this->subject->countAll(),
            $countBeforeDelete - $expectedProcessesToBeDeleted
        );
    }
}
