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

use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class QueueTest
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class QueueTest extends UnitTestCase
{

    /**
     * @var \tx_crawler_domain_queue_entry
     */
    protected $subject;

    /**
     * @test
     */
    public function getExecutionTime()
    {
        $this->subject = new \tx_crawler_domain_queue_entry(['exec_time' => 123456]);

        $this->assertEquals(
            123456,
            $this->subject->getExecutionTime()
        );
    }
}
