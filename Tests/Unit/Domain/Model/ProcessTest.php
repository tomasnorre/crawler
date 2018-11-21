<?php
namespace AOE\Crawler\Tests\Unit\Domain\Model;

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
use Nimut\TestingFramework\TestCase\UnitTestCase;

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

    public function setUp()
    {
        $this->subject = $this->getMock(Process::class, ['dummy'], [], '', false);
        $this->subject->setActive(true);
        $this->subject->setProcessId('1234');
        $this->subject->setTtl('300');
        $this->subject->setAssignedItemsCount('20');
    }

    /**
     * @test
     */
    public function setAndGetRowDoAsExpected()
    {
        $processId = 4567;
        $ttl = 600;
        $assignedItemsCount = 30;
        $systemProcessId = sha1('processId');

        $this->subject->setDeleted(false);
        $this->subject->setActive(true);
        $this->subject->setProcessId($processId);
        $this->subject->setTtl($ttl);
        $this->subject->setAssignedItemsCount($assignedItemsCount);
        $this->subject->setSystemProcessId($systemProcessId);

        $this->assertFalse($this->subject->isDeleted());
        $this->assertTrue($this->subject->isActive());

        $this->assertSame(
            $processId,
            $this->subject->getProcessId()
        );

        $this->assertSame(
            $ttl,
            $this->subject->getTtl()
        );

        $this->assertSame(
            $assignedItemsCount,
            $this->subject->getAssignedItemsCount()
        );

        $this->assertSame(
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
                'expectedState' => Process::STATE_RUNNING
            ],
            'Check that state is cancelled, Inactive and less than 100%' => [
                'active' => 0,
                'processes' => 90,
                'expectedState' => Process::STATE_CANCELLED
            ],
            'Check that state is completed, Active and 100%' => [
                'active' => 1,
                'processes' => 100,
                'expectedState' => Process::STATE_COMPLETED
            ],
            'Check that state is completed, Inactive and 100%' => [
                'active' => 0,
                'processes' => 100,
                'expectedState' => Process::STATE_COMPLETED
            ]
        ];
    }

    /**
     * @test
     *
     * @param $active
     * @param $processes
     * @param $expectedState
     *
     * @dataProvider getStateDataProvider
     */
    public function getStateReturnsExpectedState($active, $processes, $expectedState)
    {
        /** @var Process $processMock */
        $processMock = $this->getAccessibleMock(Process::class, ['isActive', 'getProgress'], [], '', false);
        $processMock->expects($this->any())->method('isActive')->will($this->returnValue($active));
        $processMock->expects($this->any())->method('getProgress')->will($this->returnValue($processes));

        $this->assertEquals(
            $expectedState,
            $processMock->getState()
        );
    }

    /**
     * @test
     *
     * @param $countItemsAssigned
     * @param $expectedProgress
     *
     * @dataProvider getProgressReturnsExpectedPercentageDataProvider
     */
    public function getProgressReturnsExpectedPercentage($countItemsAssigned, $countItemsProcessed, $expectedProgress)
    {
        /** @var Process $processMock */
        $processMock = $this->getAccessibleMock(Process::class, ['getAssignedItemsCount', 'countItemsProcessed'], [], '', false);
        $processMock->expects($this->any())->method('getAssignedItemsCount')->will($this->returnValue($countItemsAssigned));
        $processMock->expects($this->any())->method('countItemsProcessed')->will($this->returnValue($countItemsProcessed));

        $this->assertEquals(
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
                'expectedProgress' => 0
            ],
            'CountItemsAssigned is 0' => [
                'countItemsAssigned' => 0,
                'countItemsProcessed' => 8,
                'expectedProgress' => 0
            ],
            'CountItemsAssigned is higher than countItemsProcessed' => [
                'countItemsAssigned' => 100,
                'countItemsProcessed' => 8,
                'expectedProgress' => 8.0
            ],
            'CountItemsAssigned are equal countItemsProcessed' => [
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 15,
                'expectedProgress' => 100.0
            ],
            'CountItemsAssigned is lower than countItemsProcessed' => [
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 20,
                'expectedProgress' => 100.0
            ],
        ];
    }

    /**
     * @test
     *
     * @param $getTimeForFirstItem
     * @param $getTimeForLastItem
     * @param $expected
     *
     * @dataProvider getRuntimeReturnsIntegerDataProvider
     */
    public function getRuntimeReturnsInteger($getTimeForFirstItem, $getTimeForLastItem, $expected)
    {
        /** @var Process $processMock */
        $processMock = $this->getAccessibleMock(Process::class, [ 'getTimeForFirstItem', 'getTimeForLastItem'], [], '', false);
        $processMock->expects($this->any())->method('getTimeForFirstItem')->will($this->returnValue($getTimeForFirstItem));
        $processMock->expects($this->any())->method('getTimeForLastItem')->will($this->returnValue($getTimeForLastItem));

        $this->assertEquals(
            $expected,
            $processMock->getRuntime()
        );
    }

    /**
     * @return array
     */
    public function getRuntimeReturnsIntegerDataProvider()
    {
        return [
            'getTimeForFirstItem is bigger than getTimeForLastItem' => [
                'getTimeForFirstItem' => 75,
                'getTimeForLastItem' => 50,
                'expected' => -25
            ],
            'getTimeForFirstItem is smaller than getTimeForLastItem' => [
                'getTimeForFirstItem' => 55,
                'getTimeForLastItem' => 85,
                'expected' => 30
            ],
            'getTimeForFirstItem is equal to getTimeForLastItem' => [
                'getTimeForFirstItem' => 45,
                'getTimeForLastItem' => 45,
                'expected' => 0
            ],
            'getTimeForFirstItem is negative number and getTimeForLastItem is positive' => [
                'getTimeForFirstItem' => -25,
                'getTimeForLastItem' => 50,
                'expected' => 75
            ],
            'getTimeForFirstItem is positive number and getTimeForLastItem is negative' => [
                'getTimeForFirstItem' => 25,
                'getTimeForLastItem' => -50,
                'expected' => -75
            ],
        ];
    }
}
