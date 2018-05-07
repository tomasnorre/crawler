<?php
namespace AOE\Crawler\Tests\Functional\Hooks;

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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Hooks\ProcessCleanUpHook;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessCleanUpHookTest
 *
 * @package AOE\Crawler\Tests\Functional\Hooks
 */
class ProcessCleanUpHookTest extends FunctionalTestCase
{

    /**
     * @var ProcessCleanUpHook
     */
    protected $subject;

    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ProcessCleanUpHook subject */
        $this->subject = $this->objectManager->get(ProcessCleanUpHook::class);
        $this->processRepository = $this->objectManager->get(ProcessRepository::class);
        $this->queueRepository = $this->objectManager->get(QueueRepository::class);

        // Include Fixtures
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_process.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function removeActiveProcessesOlderThanOneHour()
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function removeActiveOrphanProcesses()
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function doProcessStillExists()
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function killProcess()
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function findDispatcherProcesses()
    {
        $this->markTestSkipped('Please Implement');
    }
    
    /**
     * @test
     */
    public function removeProcessFromProcesslistCalledWithProcessThatDoesNotExist()
    {
        $processCountBefore = $this->processRepository->countAll();
        $queueCountBefore = $this->queueRepository->countAll();

        $notExistingProcessId = 23456;
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $notExistingProcessId);

        $processCountAfter = $this->processRepository->countAll();
        $queueCountAfter = $this->queueRepository->countAll();

        $this->assertEquals(
            $processCountBefore,
            $processCountAfter
        );

        $this->assertEquals(
            $queueCountBefore,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function removeProcessFromProcesslistRemoveOneProcessAndNoQueueRecords()
    {
        $expectedProcessesToBeRemoved = 1;
        $expectedQueueRecordsToBeRemoved = 0;

        $processCountBefore = $this->processRepository->countAll();
        $queueCountBefore = $this->queueRepository->countAll();

        $existingProcessId = 1000;
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->countAll();
        $queueCountAfter = $this->queueRepository->countAll();

        $this->assertEquals(
            $processCountBefore - $expectedProcessesToBeRemoved,
            $processCountAfter
        );

        $this->assertEquals(
            $queueCountBefore - $expectedQueueRecordsToBeRemoved,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function removeProcessFromProcesslistRemoveOneProcessAndOneQueueRecordIsReset()
    {
        $existingProcessId = 1001;
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = $this->processRepository->countAll();
        $queueCountBefore = $this->queueRepository->countAll('process_id = ' . $existingProcessId);

        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->countAll();
        $queueCountAfter = $this->queueRepository->countAll('process_id = ' . $existingProcessId);

        $this->assertEquals(
            $processCountBefore - $expectedProcessesToBeRemoved,
            $processCountAfter
        );

        $this->assertEquals(
            1,
            $queueCountBefore
        );

        $this->assertEquals(
            0,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function createResponseArrayReturnsEmptyArray()
    {
        $emptyInputString = '';

        $this->assertEquals(
            [],
            $this->callInaccessibleMethod($this->subject, 'createResponseArray', $emptyInputString)
        );
    }

    /**
     * @test
     */
    public function createResponseArrayReturnsArray()
    {
        // Input string has multiple spacing to ensure we don't end up with an array with empty values
        $inputString = '1   2 2 4 5 6 ';
        $expectedOutputArray = ['1', '2', '2', '4', '5', '6'];

        $this->assertEquals(
            $expectedOutputArray,
            $this->callInaccessibleMethod($this->subject, 'createResponseArray', $inputString)
        );
    }
}
