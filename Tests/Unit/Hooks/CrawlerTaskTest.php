<?php
namespace AOE\Crawler\Tests\Unit\Hooks;

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
 * Class CrawlerTaskTest
 *
 * @package AOE\Crawler\Tests\Unit\Hooks
 */
class CrawlerTaskTest extends UnitTestCase
{
    /**
     * @test
     */
    public function ClassAliasMapReturnsNewClassName()
    {
        $classObject = $this->createMock('tx_crawler_scheduler_crawl', [], [], '', false);

        $this->assertInstanceOf(
            'AOE\Crawler\Task\CrawlerTask',
            $classObject
        );
    }
}
