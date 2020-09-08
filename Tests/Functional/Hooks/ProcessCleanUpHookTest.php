<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Hooks;

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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function removeActiveProcessesOlderThanOneHour(): void
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function removeActiveOrphanProcesses(): void
    {
        $this->markTestSkipped('Please Implement');
    }

    /**
     * @test
     */
    public function doProcessStillExists(): void
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    /**
     * @test
     */
    public function killProcess(): void
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    /**
     * @test
     */
    public function findDispatcherProcesses(): void
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    /**
     * @test
     */
    public function removeProcessFromProcesslistCalledWithProcessThatDoesNotExist(): void
    {
        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->findAll()->count();

        $notExistingProcessId = '23456';
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $notExistingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->findAll()->count();

        self::assertEquals(
            $processCountBefore,
            $processCountAfter
        );

        self::assertEquals(
            $queueCountBefore,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function removeProcessFromProcesslistRemoveOneProcessAndNoQueueRecords(): void
    {
        $expectedProcessesToBeRemoved = 1;
        $expectedQueueRecordsToBeRemoved = 0;

        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->findAll()->count();

        $existingProcessId = '1000';
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->findAll()->count();

        self::assertEquals(
            $processCountBefore - $expectedProcessesToBeRemoved,
            $processCountAfter
        );

        self::assertEquals(
            $queueCountBefore - $expectedQueueRecordsToBeRemoved,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function removeProcessFromProcesslistRemoveOneProcessAndOneQueueRecordIsReset(): void
    {
        $existingProcessId = '1001';
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->countAllByProcessId($existingProcessId);

        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->countAllByProcessId($existingProcessId);

        self::assertEquals(
            $processCountBefore - $expectedProcessesToBeRemoved,
            $processCountAfter
        );

        self::assertEquals(
            1,
            $queueCountBefore
        );

        self::assertEquals(
            0,
            $queueCountAfter
        );
    }

    /**
     * @test
     */
    public function createResponseArrayReturnsEmptyArray(): void
    {
        $emptyInputString = '';

        self::assertEquals(
            [],
            $this->callInaccessibleMethod($this->subject, 'createResponseArray', $emptyInputString)
        );
    }

    /**
     * @test
     */
    public function createResponseArrayReturnsArray(): void
    {
        // Input string has multiple spacing to ensure we don't end up with an array with empty values
        $inputString = '1   2 2 4 5 6 ';
        $expectedOutputArray = ['1', '2', '2', '4', '5', '6'];

        self::assertEquals(
            $expectedOutputArray,
            $this->callInaccessibleMethod($this->subject, 'createResponseArray', $inputString)
        );
    }
}
