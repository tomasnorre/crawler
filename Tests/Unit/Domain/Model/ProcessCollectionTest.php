<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Model\ProcessCollection;
use AOE\Crawler\Exception\NoIndexFoundException;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ProcessCollectionTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 * @covers \AOE\Crawler\Domain\Model\ProcessCollection
 * @covers \AOE\Crawler\Domain\Model\Process::getProcessId
 * @covers \AOE\Crawler\Domain\Model\Process::setProcessId
 */
class ProcessCollectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected \AOE\Crawler\Domain\Model\ProcessCollection $subject;

    protected function setUp(): void
    {
        $this->subject = new ProcessCollection();
    }

    /**
     * @test
     */
    public function getProcessIdsReturnsArray(): void
    {
        /** @var Process $processOne */
        $processOne = $this->getMockBuilder(Process::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processOne->setProcessId('11');

        /** @var Process $processTwo */
        $processTwo = $this->getMockBuilder(Process::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processTwo->setProcessId('13');

        $processes = [];
        $processes[] = $processOne;
        $processes[] = $processTwo;

        $collection = new ProcessCollection($processes);

        self::assertEquals(['11', '13'], $collection->getProcessIds());
    }

    /**
     * @test
     */
    public function appendThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1_593_714_821);
        $wrongObjectType = new \stdClass();
        $this->subject->append($wrongObjectType);
    }

    /**
     * @test
     */
    public function appendCrawlerDomainObject(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);
        $this->subject->append($correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(0));
    }

    /**
     * @test
     */
    public function offsetSetThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1_593_714_822);
        $wrongObjectType = new \stdClass();
        $this->subject->offsetSet(100, $wrongObjectType);
    }

    /**
     * @test
     */
    public function offsetSetAndGet(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);
        $this->subject->offsetSet(100, $correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }

    /**
     * @test
     *
     * @expectedException \Exception
     */
    public function offsetGetThrowsException(): void
    {
        self::expectException(NoIndexFoundException::class);
        self::expectExceptionCode(1_593_714_823);
        self::expectExceptionMessageMatches('/^Index.*100.*Process are not available$/');
        $correctObjectType = $this->getAccessibleMock(Process::class, ['dummy'], [], '', false);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }
}
