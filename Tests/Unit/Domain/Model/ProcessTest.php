<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Process
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessTest extends UnitTestCase
{
    /**
     * @var Process
     * @inject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = $this->createPartialMock(Process::class, ['dummy']);
        $this->subject->setActive(true);
        $this->subject->setProcessId('1234');
        $this->subject->setTtl(300);
        $this->subject->setAssignedItemsCount(20);
    }

    /**
     * @test
     */
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

        self::assertSame(
            $processId,
            $this->subject->getProcessId()
        );

        self::assertSame(
            $ttl,
            $this->subject->getTtl()
        );

        self::assertSame(
            $assignedItemsCount,
            $this->subject->getAssignedItemsCount()
        );

        self::assertSame(
            $systemProcessId,
            $this->subject->getSystemProcessId()
        );
    }

    /**
     * @return array
     */
    public function getStateDataProvider()
    {
        return [
            'Check that state is running, Active and less than 100%' => [
                'active' => 1,
                'processes' => 90,
                'expectedState' => Process::STATE_RUNNING,
            ],
            'Check that state is cancelled, Inactive and less than 100%' => [
                'active' => 0,
                'processes' => 90,
                'expectedState' => Process::STATE_CANCELLED,
            ],
            'Check that state is completed, Active and 100%' => [
                'active' => 1,
                'processes' => 100,
                'expectedState' => Process::STATE_COMPLETED,
            ],
            'Check that state is completed, Inactive and 100%' => [
                'active' => 0,
                'processes' => 100,
                'expectedState' => Process::STATE_COMPLETED,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getStateDataProvider
     */
    public function getStateReturnsExpectedState(int $active, int $processes, string $expectedState): void
    {
        /** @var MockObject|Process $processMock */
        $processMock = self::getAccessibleMock(Process::class, ['isActive', 'getProgress'], [], '', false);
        $processMock->expects($this->any())->method('isActive')->will($this->returnValue($active));
        $processMock->expects($this->any())->method('getProgress')->will($this->returnValue($processes));

        self::assertEquals(
            $expectedState,
            $processMock->getState()
        );
    }

    /**
     * @test
     *
     * @dataProvider getProgressReturnsExpectedPercentageDataProvider
     */
    public function getProgressReturnsExpectedPercentage(int $countItemsAssigned, int $countItemsProcessed, float $expectedProgress): void
    {
        /** @var MockObject|Process $processMock */
        $processMock = self::getAccessibleMock(Process::class, ['getAssignedItemsCount', 'getAmountOfItemsProcessed'], [], '', false);
        $processMock->expects($this->any())->method('getAssignedItemsCount')->will($this->returnValue($countItemsAssigned));
        $processMock->expects($this->any())->method('getAmountOfItemsProcessed')->will($this->returnValue($countItemsProcessed));

        self::assertEquals(
            $expectedProgress,
            $processMock->getProgress()
        );
    }

    /**
     * @return array
     */
    public function getProgressReturnsExpectedPercentageDataProvider()
    {
        return [
            'CountItemsAssigned is negative number' => [
                'countItemsAssigned' => -2,
                'countItemsProcessed' => 8,
                'expectedProgress' => 0,
            ],
            'CountItemsAssigned is 0' => [
                'countItemsAssigned' => 0,
                'countItemsProcessed' => 8,
                'expectedProgress' => 0,
            ],
            'CountItemsAssigned is higher than countItemsProcessed' => [
                'countItemsAssigned' => 100,
                'countItemsProcessed' => 8,
                'expectedProgress' => 8.0,
            ],
            'CountItemsAssigned are equal countItemsProcessed' => [
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 15,
                'expectedProgress' => 100.0,
            ],
            'CountItemsAssigned is lower than countItemsProcessed' => [
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 20,
                'expectedProgress' => 100.0,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getRuntimeReturnsIntegerDataProvider
     */
    public function getRuntimeReturnsInteger(array $getTimeForFirstItem, array $getTimeForLastItem, int $expected): void
    {
        /** @var MockObject|QueueRepository $queueRepositoryMock */
        $queueRepositoryMock = self::getAccessibleMock(QueueRepository::class, ['findOldestEntryForProcess', 'findYoungestEntryForProcess'], [], '', false);
        $queueRepositoryMock->expects($this->any())->method('findOldestEntryForProcess')->will($this->returnValue($getTimeForLastItem));
        $queueRepositoryMock->expects($this->any())->method('findYoungestEntryForProcess')->will($this->returnValue($getTimeForFirstItem));

        $this->subject->_setProperty('queueRepository', $queueRepositoryMock);
        self::assertEquals(
            $expected,
            $this->subject->getRuntime()
        );
    }

    /**
     * @return array
     */
    public function getRuntimeReturnsIntegerDataProvider()
    {
        return [
            'getTimeForFirstItem is bigger than getTimeForLastItem' => [
                'getTimeForFirstItem' => ['exec_time' => 75],
                'getTimeForLastItem' => ['exec_time' => 50],
                'expected' => -25,
            ],
            'getTimeForFirstItem is smaller than getTimeForLastItem' => [
                'getTimeForFirstItem' => ['exec_time' => 55],
                'getTimeForLastItem' => ['exec_time' => 85],
                'expected' => 30,
            ],
            'getTimeForFirstItem is equal to getTimeForLastItem' => [
                'getTimeForFirstItem' => ['exec_time' => 45],
                'getTimeForLastItem' => ['exec_time' => 45],
                'expected' => 0,
            ],
            'getTimeForFirstItem is negative number and getTimeForLastItem is positive' => [
                'getTimeForFirstItem' => ['exec_time' => -25],
                'getTimeForLastItem' => ['exec_time' => 50],
                'expected' => 75,
            ],
            'getTimeForFirstItem is positive number and getTimeForLastItem is negative' => [
                'getTimeForFirstItem' => ['exec_time' => 25],
                'getTimeForLastItem' => ['exec_time' => -50],
                'expected' => -75,
            ],
        ];
    }
}
