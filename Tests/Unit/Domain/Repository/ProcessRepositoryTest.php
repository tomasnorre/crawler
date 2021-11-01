<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Repository;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessRepositoryTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Repository
 * @covers \AOE\Crawler\Domain\Repository\ProcessRepository
 */
class ProcessRepositoryTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider getLimitFromItemCountAndOffsetDataProvider
     */
    public function getLimitFromItemCountAndOffset(int $itemCount, int $offset, string $expected): void
    {
        self::assertEquals(
            $expected,
            ProcessRepository::getLimitFromItemCountAndOffset($itemCount, $offset)
        );
    }

    public function getLimitFromItemCountAndOffsetDataProvider(): iterable
    {
        yield 'Both itemCount and offset bigger as minimum value' => [
            'itemCount' => 10,
            'offset' => 100,
            'expected' => '100, 10',
        ];
        yield 'itemCount smaller than minimum value, offset bigger than minimum value' => [
            'itemCount' => -1,
            'offset' => 10,
            'expected' => '10, 20',
        ];
        yield 'itemCount bigger than minimum value, offset smaller than minimum value' => [
            'itemCount' => 10,
            'offset' => -1,
            'expected' => '0, 10',
        ];
        yield 'Both itemCount and offset are smaller than minimum value' => [
            'itemCount' => -1,
            'offset' => -1,
            'expected' => '0, 20',
        ];
        yield 'Both on minimum values' => [
            'itemCount' => 1,
            'offset' => 0,
            'expected' => '0, 1',
        ];
        yield 'Both itemCount and offset is 0' => [
            'itemCount' => 0,
            'offset' => 0,
            'expected' => '0, 20',
        ];
    }
}
