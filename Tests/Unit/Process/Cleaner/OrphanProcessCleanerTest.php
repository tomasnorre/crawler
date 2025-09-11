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
use AOE\Crawler\Process\Cleaner\OrphanProcessCleaner;
use AOE\Crawler\Process\ProcessManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(OrphanProcessCleaner::class)]
class OrphanProcessCleanerTest extends UnitTestCase
{
    private ProcessRepository $processRepository;
    private QueueRepository $queueRepository;
    private ProcessManagerInterface $processManager;
    private OrphanProcessCleaner $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->queueRepository = $this->createMock(QueueRepository::class);
        $this->processManager = $this->createMock(ProcessManagerInterface::class);

        $this->subject = new OrphanProcessCleaner(
            $this->processRepository,
            $this->queueRepository,
            $this->processManager
        );
    }

    #[Test]
    public function cleanSkipsProcessesWithSystemIdLessOrEqualOne(): void
    {
        $this->processRepository
            ->expects(self::once())
            ->method('getActiveOrphanProcesses')
            ->willReturn([
                ['system_process_id' => 0, 'process_id' => 'p-0'],
                ['system_process_id' => 1, 'process_id' => 'p-1'],
            ]);

        $this->processManager->expects(self::never())->method('findDispatcherProcesses');
        $this->processRepository->expects(self::never())->method('removeByProcessId');
        $this->queueRepository->expects(self::never())->method('unsetQueueProcessId');

        $this->subject->clean();
    }

    #[Test]
    public function cleanRemovesProcessIfNoDispatcherProcessesFound(): void
    {
        $processId = 'p-100';

        $this->processRepository
            ->expects(self::once())
            ->method('getActiveOrphanProcesses')
            ->willReturn([['system_process_id' => 200, 'process_id' => $processId]]);

        $this->processManager
            ->expects(self::once())
            ->method('findDispatcherProcesses')
            ->willReturn([]); // empty list

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
    public function cleanDoesNotRemoveProcessIfDispatcherMatchesSystemProcessId(): void
    {
        $systemProcessId = 300;
        $processId = 'p-200';

        $this->processRepository
            ->expects(self::once())
            ->method('getActiveOrphanProcesses')
            ->willReturn([['system_process_id' => $systemProcessId, 'process_id' => $processId]]);

        // Dispatcher returns a string like "typo3 300 ..." -> parsed into [ 'typo3', '300', ... ]
        $this->processManager
            ->expects(self::once())
            ->method('findDispatcherProcesses')
            ->willReturn(['typo3 300 something']);

        $this->processRepository->expects(self::never())->method('removeByProcessId');
        $this->queueRepository->expects(self::never())->method('unsetQueueProcessId');

        $this->subject->clean();
    }

    #[Test]
    public function cleanRemovesProcessIfDispatcherDoesNotMatchSystemProcessId(): void
    {
        $systemProcessId = 400;
        $processId = 'p-300';

        $this->processRepository
            ->expects(self::once())
            ->method('getActiveOrphanProcesses')
            ->willReturn([['system_process_id' => $systemProcessId, 'process_id' => $processId]]);

        // Dispatcher returns process with a different PID -> should trigger removal
        $this->processManager
            ->expects(self::once())
            ->method('findDispatcherProcesses')
            ->willReturn(['typo3 999 something']);

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
