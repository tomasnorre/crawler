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

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class Process
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessTest extends UnitTestCase
{

    /**
     * @var \tx_crawler_domain_process
     * @inject
     */
    protected $subject;

    public function setUp()
    {
        $processObjectArray = array(
            'active' => '1',
            'process_id' => '1234',
            'ttl' => '300',
            'assigned_items_count' => '20'
        );
        $this->subject = new \tx_crawler_domain_process($processObjectArray);
    }

    /**
     * @test
     */
    public function getActiveReturnsInteger()
    {
        $this->assertEquals(
            1,
            $this->subject->getActive()
        );
    }

    /**
     * @test
     */
    public function getProcessIdReturnsInteger()
    {
        $this->assertEquals(
            1234,
            $this->subject->getProcess_id()
        );
    }

    /**
     * @test
     */
    public function getTTLReturnsInteger()
    {
        $this->assertEquals(
            300,
            $this->subject->getTTL()
        );
    }

    /**
     * @test
     */
    public function countItemsAssignedReturnsInteger()
    {
        $this->assertEquals(
            20,
            $this->subject->countItemsAssigned()
        );
    }

    /**
     * @return array
     */
    public function getStateDataProvider()
    {
        return array(
            'Check that state is running, Active and less than 100%' => array(
                'active' => 1,
                'processes' => 90,
                'expectedState' => \tx_crawler_domain_process::STATE_RUNNING
            ),
            'Check that state is cancelled, Inactive and less than 100%' => array(
                'active' => 0,
                'processes' => 90,
                'expectedState' => \tx_crawler_domain_process::STATE_CANCELLED
            ),
            'Check that state is completed, Active and 100%' => array(
                'active' => 1,
                'processes' => 100,
                'expectedState' => \tx_crawler_domain_process::STATE_COMPLETED
            ),
            'Check that state is completed, Inactive and 100%' => array(
                'active' => 0,
                'processes' => 100,
                'expectedState' => \tx_crawler_domain_process::STATE_COMPLETED
            )
        );
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
        /** @var \tx_crawler_domain_process $processMock */
        $processMock = $this->getAccessibleMock('tx_crawler_domain_process', array('getActive', 'getProgress'));
        $processMock->expects($this->any())->method('getActive')->will($this->returnValue($active));
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
        /** @var \tx_crawler_domain_process $processMock */
        $processMock = $this->getAccessibleMock('tx_crawler_domain_process', array('countItemsAssigned', 'countItemsProcessed'));
        $processMock->expects($this->any())->method('countItemsAssigned')->will($this->returnValue($countItemsAssigned));
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
        return array(
            'CountItemsAssigned is negative number' => array(
                'countItemsAssigned' => -2,
                'countItemsProcessed' => 8,
                'expectedProgress' => 0
            ),
            'CountItemsAssigned is 0' => array(
                'countItemsAssigned' => 0,
                'countItemsProcessed' => 8,
                'expectedProgress' => 0
            ),
            'CountItemsAssigned is higher than countItemsProcessed' => array(
                'countItemsAssigned' => 100,
                'countItemsProcessed' => 8,
                'expectedProgress' => 8.0
            ),
            'CountItemsAssigned are equal countItemsProcessed' => array(
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 15,
                'expectedProgress' => 100.0
            ),
            'CountItemsAssigned is lower than countItemsProcessed' => array(
                'countItemsAssigned' => 15,
                'countItemsProcessed' => 20,
                'expectedProgress' => 100.0
            ),
        );
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
        /** @var \tx_crawler_domain_process $processMock */
        $processMock = $this->getAccessibleMock('tx_crawler_domain_process', array( 'getTimeForFirstItem', 'getTimeForLastItem'));
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
        return array(
            'getTimeForFirstItem is bigger than getTimeForLastItem' => array(
                'getTimeForFirstItem' => 75,
                'getTimeForLastItem' => 50,
                'expected' => -25
            ),
            'getTimeForFirstItem is smaller than getTimeForLastItem' => array(
                'getTimeForFirstItem' => 55,
                'getTimeForLastItem' => 85,
                'expected' => 30
            ),
            'getTimeForFirstItem is equal to getTimeForLastItem' => array(
                'getTimeForFirstItem' => 45,
                'getTimeForLastItem' => 45,
                'expected' => 0
            ),
            'getTimeForFirstItem is negative number and getTimeForLastItem is positive' => array(
                'getTimeForFirstItem' => -25,
                'getTimeForLastItem' => 50,
                'expected' => 75
            ),
            'getTimeForFirstItem is positive number and getTimeForLastItem is negative' => array(
                'getTimeForFirstItem' => 25,
                'getTimeForLastItem' => -50,
                'expected' => -75
            ),
        );
    }
}