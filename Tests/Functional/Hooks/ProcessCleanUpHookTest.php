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
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProcessCleanUpHookTest
 *
 * @package AOE\Crawler\Tests\Functional\Hooks
 */
class ProcessCleanUpHookTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use BackendRequestTestTrait;

    protected ProcessCleanUpHook $subject;
    protected ProcessRepository $processRepository;
    protected QueueRepository $queueRepository;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBackendRequest();

        /** @var ProcessCleanUpHook $this->subject */
        $this->subject = GeneralUtility::makeInstance(ProcessCleanUpHook::class);
        $this->processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        // Include Fixtures
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_process.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
    }

    protected function tearDown(): void
    {
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removeActiveProcessesOlderThanOneHour(): never
    {
        $this->markTestSkipped('Please Implement');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removeActiveOrphanProcesses(): never
    {
        $this->markTestSkipped('Please Implement');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function doProcessStillExists(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function killProcess(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function findDispatcherProcesses(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removeProcessFromProcesslistCalledWithProcessThatDoesNotExist(): void
    {
        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->findAll()->count();

        $notExistingProcessId = '23456';
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $notExistingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->findAll()->count();

        self::assertEquals($processCountBefore, $processCountAfter);

        self::assertEquals($queueCountBefore, $queueCountAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removeProcessFromProcesslistRemoveOneProcessAndNoQueueRecords(): void
    {
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->findAll()->count();

        $existingProcessId = '1000';
        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->findAll()->count();

        self::assertEquals($processCountBefore - $expectedProcessesToBeRemoved, $processCountAfter);
        self::assertEquals($queueCountBefore, $queueCountAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removeProcessFromProcesslistRemoveOneProcessAndOneQueueRecordIsReset(): void
    {
        $existingProcessId = '1001';
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = $this->processRepository->findAll()->count();
        $queueCountBefore = $this->queueRepository->findBy(['processId' => $existingProcessId])->count();

        $this->callInaccessibleMethod($this->subject, 'removeProcessFromProcesslist', $existingProcessId);

        $processCountAfter = $this->processRepository->findAll()->count();
        $queueCountAfter = $this->queueRepository->findBy(['processId' => $existingProcessId])->count();

        self::assertEquals($processCountBefore - $expectedProcessesToBeRemoved, $processCountAfter);

        self::assertEquals(1, $queueCountBefore);

        self::assertEquals(0, $queueCountAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createResponseArrayReturnsEmptyArray(): void
    {
        $emptyInputString = '';

        self::assertEquals(
            [],
            $this->callInaccessibleMethod($this->subject, 'createResponseArray', $emptyInputString)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
