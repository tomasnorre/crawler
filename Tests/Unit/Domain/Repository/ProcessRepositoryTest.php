<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Repository;

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessRepositoryTest
 */
class ProcessRepositoryTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider getLimitFromItemCountAndOffsetDataProvider
     */
    public function getLimitFromItemCountAndOffset($itemCount, $offset, $expected): void
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
