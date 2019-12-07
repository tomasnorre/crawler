<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Task;

use Nimut\TestingFramework\TestCase\UnitTestCase;

class ProcessCleanupTaskTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classAliasMapReturnsNewClassName(): void
    {
        $classObject = $this->createMock('\AOE\Crawler\Tasks\ProcessCleanupTask', [], [], '', false);

        self::assertInstanceOf(
            'AOE\Crawler\Task\ProcessCleanupTask',
            $classObject
        );
    }
}
