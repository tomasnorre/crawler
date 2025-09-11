<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Process\Cleaner;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Process\Cleaner\OldProcessCleaner;
use AOE\Crawler\Process\ProcessManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(OldProcessCleaner::class)]
class OldProcessCleanerTest extends UnitTestCase
{
    private ProcessRepository $processRepository;
    private QueueRepository $queueRepository;
    private ProcessManagerInterface $processManager;
    private OldProcessCleaner $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->queueRepository = $this->createMock(QueueRepository::class);
        $this->processManager = $this->createMock(ProcessManagerInterface::class);

        $this->subject = new OldProcessCleaner(
            $this->processRepository,
            $this->queueRepository,
            $this->processManager
        );
    }

    #[Test]
    public function cleanDoesNothingWhenRepositoryReturnsNonArray(): void
    {
        $this->processRepository
            ->expects(self::once())
            ->method('getActiveProcessesOlderThanOneHour')
            ->willReturn(null);

        $this->processManager->expects(self::never())->method('processExists');
        $this->processRepository->expects(self::never())->method('removeByProcessId');
        $this->queueRepository->expects(self::never())->method('unsetQueueProcessId');

        $this->subject->clean();
    }

    #[Test]
    public function cleanSkipsProcessesWithSystemIdLessOrEqualOne(): void
    {
        $this->processRepository
            ->expects(self::once())
            ->method('getActiveProcessesOlderThanOneHour')
            ->willReturn([
                ['system_process_id' => 0, 'process_id' => 'skip-me'],
                ['system_process_id' => 1, 'process_id' => 'skip-me-too'],
            ]);

        $this->processManager->expects(self::never())->method('processExists');
        $this->processRepository->expects(self::never())->method('removeByProcessId');
        $this->queueRepository->expects(self::never())->method('unsetQueueProcessId');

        $this->subject->clean();
    }

    #[Test]
    public function cleanKillsAndRemovesProcessesThatStillExist(): void
    {
        $systemProcessId = 1234;
        $processId = 'p-1';

        $this->processRepository
            ->expects(self::once())
            ->method('getActiveProcessesOlderThanOneHour')
            ->willReturn([['system_process_id' => $systemProcessId, 'process_id' => $processId]]);

        $this->processManager
            ->expects(self::once())
            ->method('processExists')
            ->with($systemProcessId)
            ->willReturn(true);

        $this->processManager
            ->expects(self::once())
            ->method('killProcess')
            ->with($systemProcessId);

        $this->processRepository
            ->expects(self::once())
            ->method('removeByProcessId')
            ->with($processId);

        $this->queueRepository
            ->expects(self::once())
            ->method('unsetQueueProcessId')
            ->with($processId);

        $this->subject->clean();
    }

    #[Test]
    public function cleanRemovesProcessesThatDoNotExist(): void
    {
        $systemProcessId = 5678;
        $processId = 'p-2';

        $this->processRepository
            ->expects(self::once())
            ->method('getActiveProcessesOlderThanOneHour')
            ->willReturn([['system_process_id' => $systemProcessId, 'process_id' => $processId]]);

        $this->processManager
            ->expects(self::once())
            ->method('processExists')
            ->with($systemProcessId)
            ->willReturn(false);

        $this->processManager->expects(self::never())->method('killProcess');

        $this->processRepository
            ->expects(self::once())
            ->method('removeByProcessId')
            ->with($processId);

        $this->queueRepository
            ->expects(self::once())
            ->method('unsetQueueProcessId')
            ->with($processId);

        $this->subject->clean();
    }
}
