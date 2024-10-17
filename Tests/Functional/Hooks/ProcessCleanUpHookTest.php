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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @package AOE\Crawler\Tests\Functional\Hooks
 */
class ProcessCleanUpHookTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_process.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.csv');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function removeActiveProcessesOlderThanOneHour(): never
    {
        $this->markTestSkipped('Please Implement');
    }

    #[Test]
    public function removeActiveOrphanProcesses(): never
    {
        $this->markTestSkipped('Please Implement');
    }

    #[Test]
    public function doProcessStillExists(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[Test]
    public function killProcess(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[Test]
    public function findDispatcherProcesses(): never
    {
        $this->markTestSkipped('Skipped due to differences between windows and *nix');
    }

    #[Test]
    public function removeProcessFromProcesslistCalledWithProcessThatDoesNotExist(): void
    {
        $processCountBefore = count($this->processRepository->findAll());
        $queueCountBefore = count($this->queueRepository->findAll());

        $notExistingProcessId = '23456';
        $this->subject->removeProcessFromProcesslist($notExistingProcessId);

        $processCountAfter = count($this->processRepository->findAll());
        $queueCountAfter = count($this->queueRepository->findAll());

        self::assertEquals($processCountBefore, $processCountAfter);

        self::assertEquals($queueCountBefore, $queueCountAfter);
    }

    #[Test]
    public function removeProcessFromProcesslistRemoveOneProcessAndNoQueueRecords(): void
    {
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = count($this->processRepository->findAll());
        $queueCountBefore = count($this->queueRepository->findAll());

        $existingProcessId = '1000';
        $this->subject->removeProcessFromProcesslist($existingProcessId);

        $processCountAfter = count($this->processRepository->findAll());
        $queueCountAfter = count($this->queueRepository->findAll());

        self::assertEquals($processCountBefore - $expectedProcessesToBeRemoved, $processCountAfter);
        self::assertEquals($queueCountBefore, $queueCountAfter);
    }

    #[Test]
    public function removeProcessFromProcesslistRemoveOneProcessAndOneQueueRecordIsReset(): void
    {
        $existingProcessId = '1001';
        $expectedProcessesToBeRemoved = 1;

        $processCountBefore = count($this->processRepository->findAll());
        $queueCountBefore = count($this->queueRepository->findByProcessId($existingProcessId));

        $this->subject->removeProcessFromProcesslist($existingProcessId);

        $processCountAfter = count($this->processRepository->findAll());
        $queueCountAfter = count($this->queueRepository->findByProcessId($existingProcessId));

        self::assertEquals($processCountBefore - $expectedProcessesToBeRemoved, $processCountAfter);

        self::assertEquals(1, $queueCountBefore);

        self::assertEquals(0, $queueCountAfter);
    }

    #[Test]
    public function createResponseArrayReturnsEmptyArray(): void
    {
        $emptyInputString = '';

        self::assertEquals([], $this->subject->createResponseArray($emptyInputString));
    }

    #[Test]
    public function createResponseArrayReturnsArray(): void
    {
        // Input string has multiple spacing to ensure we don't end up with an array with empty values
        $inputString = '1   2 2 4 5 6 ';
        $expectedOutputArray = ['1', '2', '2', '4', '5', '6'];

        self::assertEquals($expectedOutputArray, $this->subject->createResponseArray($inputString));
    }
}
