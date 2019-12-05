<?php

namespace AOE\Crawler\Tests\Unit\Domain\Repository;

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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessRepositoryTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Repository
 */
class ProcessRepositoryTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider getLimitFromItemCountAndOffsetDataProvider
     */
    public function getLimitFromItemCountAndOffset($itemCount, $offset, $expected)
    {
        self::assertEquals(
            $expected,
            ProcessRepository::getLimitFromItemCountAndOffset($itemCount, $offset)
        );
    }

    /**
     * @return array
     */
    public function getLimitFromItemCountAndOffsetDataProvider()
    {
        return [
            'Both itemCount and offset bigger as minimum value' => [
                'itemCount' => 10,
                'offset' => 100,
                'expected' => '100, 10',
            ],
            'itemCount smaller than minimum value, offset bigger than minimum value' => [
                'itemCount' => -1,
                'offset' => 10,
                'expected' => '10, 20',
            ],
            'itemCount bigger than minimum value, offset smaller than minimum value' => [
                'itemCount' => 10,
                'offset' => -1,
                'expected' => '0, 10',
            ],
            'Both itemCount and offset are smaller than minimum value' => [
                'itemCount' => -1,
                'offset' => -1,
                'expected' => '0, 20',
            ],
        ];
    }
}
