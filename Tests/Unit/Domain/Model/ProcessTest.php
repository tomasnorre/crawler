<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\QueueRepository;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Process::class)]
class ProcessTest extends UnitTestCase
{
    protected \AOE\Crawler\Domain\Model\Process $subject;
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->createPartialMock(Process::class, []);
        $this->subject->setActive(true);
        $this->subject->setProcessId('1234');
        $this->subject->setTtl(300);
        $this->subject->setAssignedItemsCount(20);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setAndGetRowDoAsExpected(): void
    {
        $processId = '4567';
        $ttl = 600;
        $assignedItemsCount = 30;
        $systemProcessId = sha1('processId');

        $this->subject->setDeleted(false);
        $this->subject->setActive(true);
        $this->subject->setProcessId($processId);
        $this->subject->setTtl($ttl);
        $this->subject->setAssignedItemsCount($assignedItemsCount);
        $this->subject->setSystemProcessId($systemProcessId);

        self::assertFalse($this->subject->isDeleted());
        self::assertTrue($this->subject->isActive());

        self::assertSame($processId, $this->subject->getProcessId());

        self::assertSame($ttl, $this->subject->getTtl());

        self::assertSame($assignedItemsCount, $this->subject->getAssignedItemsCount());

        self::assertSame($systemProcessId, $this->subject->getSystemProcessId());
    }

    public static function getStateDataProvider(): iterable
    {
        yield 'Check that state is running, Active and less than 100%' => [
            'active' => 1,
            'processes' => 90,
            'expectedState' => Process::STATE_RUNNING,
        ];
        yield 'Check that state is cancelled, Inactive and less than 100%' => [
            'active' => 0,
            'processes' => 90,
            'expectedState' => Process::STATE_CANCELLED,
        ];
        yield 'Check that state is completed, Active and 100%' => [
            'active' => 1,
            'processes' => 100,
            'expectedState' => Process::STATE_COMPLETED,
        ];
        yield 'Check that state is completed, Inactive and 100%' => [
            'active' => 0,
            'processes' => 100,
            'expectedState' => Process::STATE_COMPLETED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getStateDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getStateReturnsExpectedState(int $active, int $processes, string $expectedState): void
    {
        /** @var MockObject|Process $processMock */
        $processMock = self::getAccessibleMock(Process::class, ['isActive', 'getProgress'], [], '', false);
        $processMock->expects($this->any())->method('isActive')->willReturn((bool) $active);
        $processMock->expects($this->any())->method('getProgress')->willReturn((float) $processes);

        self::assertEquals($expectedState, $processMock->getState());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getProgressReturnsExpectedPercentageDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getProgressReturnsExpectedPercentage(
        int $countItemsAssigned,
        int $countItemsProcessed,
        float $expectedProgress
    ): void {
        /** @var MockObject|Process $processMock */
        $processMock = self::getAccessibleMock(
            Process::class,
            ['getAssignedItemsCount', 'getAmountOfItemsProcessed'],
            [],
            '',
            false
        );
        $processMock->expects($this->any())->method('getAssignedItemsCount')->willReturn($countItemsAssigned);
        $processMock->expects($this->any())->method('getAmountOfItemsProcessed')->willReturn($countItemsProcessed);

        self::assertEquals($expectedProgress, $processMock->getProgress());
    }

    public static function getProgressReturnsExpectedPercentageDataProvider(): iterable
    {
        yield 'CountItemsAssigned is negative number' => [
            'countItemsAssigned' => -2,
            'countItemsProcessed' => 8,
            'expectedProgress' => 0.0,
        ];
        yield 'CountItemsAssigned is 0' => [
            'countItemsAssigned' => 0,
            'countItemsProcessed' => 8,
            'expectedProgress' => 0.0,
        ];
        yield 'CountItemsAssigned is higher than countItemsProcessed' => [
            'countItemsAssigned' => 100,
            'countItemsProcessed' => 8,
            'expectedProgress' => 8.0,
        ];
        yield 'CountItemsAssigned are equal countItemsProcessed' => [
            'countItemsAssigned' => 15,
            'countItemsProcessed' => 15,
            'expectedProgress' => 100.0,
        ];
        yield 'CountItemsAssigned is lower than countItemsProcessed' => [
            'countItemsAssigned' => 15,
            'countItemsProcessed' => 20,
            'expectedProgress' => 100.0,
        ];
        yield '100%' => [
            'countItemsAssigned' => 100,
            'countItemsProcessed' => 100,
            'expectedProgress' => 100.0,
        ];
        yield 'result higher than 100, Testing the round if $res > 100' => [
            'countItemsAssigned' => 100,
            'countItemsProcessed' => 101,
            'expectedProgress' => 100.0,
        ];
        yield 'Comma numbers' => [
            'countItemsAssigned' => 15,
            'countItemsProcessed' => 14,
            'expectedProgress' => 93.0,
        ];
        yield 'Comma number that would round down' => [
            'countItemsAssigned' => 14,
            'countItemsProcessed' => 14,
            'expectedProgress' => 100.0,
        ];
        yield 'To make sure that floor() break the result (mutation)' => [
            'countItemsAssigned' => 99,
            'countItemsProcessed' => 98,
            'expectedProgress' => 99.0,
        ];
        yield 'To make sure that ceil() break the result (mutation)' => [
            'countItemsAssigned' => 95,
            'countItemsProcessed' => 85,
            'expectedProgress' => 89.0,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRuntimeReturnsIntegerDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getRuntimeReturnsInteger(array $getTimeForFirstItem, array $getTimeForLastItem, int $expected): void
    {
        /** @var MockObject|QueueRepository $queueRepositoryMock */
        $queueRepositoryMock = self::getAccessibleMock(
            QueueRepository::class,
            ['findOldestEntryForProcess', 'findYoungestEntryForProcess'],
            [],
            '',
            false
        );
        $queueRepositoryMock->expects($this->any())->method('findOldestEntryForProcess')->willReturn(
            $getTimeForLastItem
        );
        $queueRepositoryMock->expects($this->any())->method('findYoungestEntryForProcess')->willReturn(
            $getTimeForFirstItem
        );

        $this->subject->_setProperty('queueRepository', $queueRepositoryMock);
        self::assertEquals($expected, $this->subject->getRuntime());
    }

    public static function getRuntimeReturnsIntegerDataProvider(): iterable
    {
        yield 'getTimeForFirstItem is bigger than getTimeForLastItem' => [
            'getTimeForFirstItem' => [
                'exec_time' => 75,
            ],
            'getTimeForLastItem' => [
                'exec_time' => 50,
            ],
            'expected' => -25,
        ];
        yield 'getTimeForFirstItem is smaller than getTimeForLastItem' => [
            'getTimeForFirstItem' => [
                'exec_time' => 55,
            ],
            'getTimeForLastItem' => [
                'exec_time' => 85,
            ],
            'expected' => 30,
        ];
        yield 'getTimeForFirstItem is equal to getTimeForLastItem' => [
            'getTimeForFirstItem' => [
                'exec_time' => 45,
            ],
            'getTimeForLastItem' => [
                'exec_time' => 45,
            ],
            'expected' => 0,
        ];
        yield 'getTimeForFirstItem is negative number and getTimeForLastItem is positive' => [
            'getTimeForFirstItem' => [
                'exec_time' => -25,
            ],
            'getTimeForLastItem' => [
                'exec_time' => 50,
            ],
            'expected' => 75,
        ];
        yield 'getTimeForFirstItem is positive number and getTimeForLastItem is negative' => [
            'getTimeForFirstItem' => [
                'exec_time' => 25,
            ],
            'getTimeForLastItem' => [
                'exec_time' => -50,
            ],
            'expected' => -75,
        ];
        yield 'getTimeForFirstItem and getTimeForLastItem are both invalid arrays' => [
            'getTimeForFirstItem' => [
                'invalid_exec_time' => 0,
            ],
            'getTimeForLastItem' => [
                'invalid_exec_time' => 0,
            ],
            'expected' => 0,
        ];
    }

    public function injectSubject(Process $subject): void
    {
        $this->subject = $subject;
    }
}
