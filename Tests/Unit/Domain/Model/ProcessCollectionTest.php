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
use AOE\Crawler\Domain\Model\ProcessCollection;
use AOE\Crawler\Utility\BackendUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessCollectionTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class ProcessCollectionTest extends UnitTestCase
{

    /**
     * @var ProcessCollection
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new ProcessCollection();
    }

    /**
     * @test
     */
    public function getProcessIdsReturnsArray()
    {
        /** @var Process $processOne */
        $processOne = $this->getMockBuilder(Process::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processOne->setProcessId(11);

        /** @var Process $processTwo */
        $processTwo = $this->getMockBuilder(Process::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processTwo->setProcessId(13);

        $processes = [];
        $processes[] = $processOne;
        $processes[] = $processTwo;

        $collection = new ProcessCollection($processes);

        $this->assertEquals(
            ['11', '13'],
            $collection->getProcessIds()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function appendThrowsException()
    {
        $wrongObjectType = new BackendUtility();
        $this->subject->append($wrongObjectType);
    }

    /**
     * @test
     */
    public function appendCrawlerDomainObject()
    {
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);
        $this->subject->append($correctObjectType);

        $this->assertEquals(
            $correctObjectType,
            $this->subject->offsetGet(0)
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function offsetSetThrowsException()
    {
        $wrongObjectType = new BackendUtility();
        $this->subject->offsetSet(100, $wrongObjectType);
    }

    /**
     * @test
     */
    public function offsetSetAndGet()
    {
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);
        $this->subject->offsetSet(100, $correctObjectType);

        $this->assertEquals(
            $correctObjectType,
            $this->subject->offsetGet(100)
        );
    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function offsetGetThrowsException()
    {
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);

        $this->assertEquals(
            $correctObjectType,
            $this->subject->offsetGet(100)
        );
    }
}
