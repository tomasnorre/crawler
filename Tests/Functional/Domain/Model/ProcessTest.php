<?php
namespace AOE\Crawler\Tests\Functional\Domain\Model;

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
use AOE\Crawler\Domain\Model\Queue;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessTest
 * @package AOE\Crawler\Tests\Functional\Domain\Model
 */
class ProcessTest extends FunctionalTestCase
{

    /**
     * @var Process
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        $objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManger->get(Process::class);
    }

    /**
     * @test
     */
    public function getTimeForFirstItem()
    {
        $mockedQueueObject = new Queue();
        $mockedQueueObject->setExecTime(20);
        $mockedQueueRepository = $this->getAccessibleMock(QueueRepository::class, ['findYoungestEntryForProcess'], [], '', true);
        $mockedQueueRepository
            ->expects($this->any())
            ->method('findYoungestEntryForProcess')
            ->will($this->returnValue($mockedQueueObject));

        $this->inject($this->subject,'queueRepository', $mockedQueueRepository);

        $this->assertEquals(
            20,
            $this->subject->getTimeForFirstItem()
        );
    }

    /**
     * @test
     */
    public function getTimeForLastItem()
    {
        $mockedQueueObject = new Queue();
        $mockedQueueObject->setExecTime(30);
        $mockedQueueRepository = $this->getAccessibleMock(QueueRepository::class, ['findOldestEntryForProcess'], [], '', true);
        $mockedQueueRepository
            ->expects($this->any())
            ->method('findOldestEntryForProcess')
            ->will($this->returnValue($mockedQueueObject));

        $this->inject($this->subject,'queueRepository', $mockedQueueRepository);

        $this->assertEquals(
            30,
            $this->subject->getTimeForLastItem()
        );
    }

    /**
     * @test
     */
    public function countItemsProcessed()
    {
        $this->markTestSkipped('Skipped as the CountItemsProcessed() is only calling a function on the queueRepository');
    }

    /**
     * @test
     */
    public function countItemsToProcess()
    {
        $this->markTestSkipped('Skipped as the CountItemsProcessed() is only calling a function on the queueRepository');
    }
}
