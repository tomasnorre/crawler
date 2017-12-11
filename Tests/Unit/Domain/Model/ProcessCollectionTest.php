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
     * @var \tx_crawler_domain_process_collection
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new \tx_crawler_domain_process_collection();
    }

    /**
     * @test
     */
    public function getProcessIdsReturnsArray()
    {
        $row1 = ['process_id' => 11];
        $row2 = ['process_id' => 13];

        $processes = [];
        $processes[] = new \tx_crawler_domain_process($row1);
        $processes[] = new \tx_crawler_domain_process($row2);

        $collection = new \tx_crawler_domain_process_collection($processes);

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
        $correctObjectType = new \tx_crawler_domain_process();
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
        $correctObjectType = new \tx_crawler_domain_process();
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
        $correctObjectType = new \tx_crawler_domain_process();

        $this->assertEquals(
            $correctObjectType,
            $this->subject->offsetGet(100)
        );
    }
}
