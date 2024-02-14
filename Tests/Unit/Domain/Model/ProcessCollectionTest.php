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
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\ProcessCollection::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Process::class)]
class ProcessCollectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected \AOE\Crawler\Domain\Model\ProcessCollection $subject;

    protected function setUp(): void
    {
        $this->subject = new ProcessCollection();
    }

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProcessIdsReturnsArray(): void
    {
        /** @var Process $processOne */
        $processOne = $this->getMockBuilder(Process::class)
            ->onlyMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processOne->setProcessId('11');

        /** @var Process $processTwo */
        $processTwo = $this->getMockBuilder(Process::class)
            ->onlyMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $processTwo->setProcessId('13');

        $processes = [];
        $processes[] = $processOne;
        $processes[] = $processTwo;

        $collection = new ProcessCollection($processes);

        self::assertEquals(['11', '13'], $collection->getProcessIds());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function appendThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1_593_714_821);
        $wrongObjectType = new \stdClass();
        $this->subject->append($wrongObjectType);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function appendCrawlerDomainObject(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);
        $this->subject->append($correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1_593_714_822);
        $wrongObjectType = new \stdClass();
        $this->subject->offsetSet(100, $wrongObjectType);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetSetAndGet(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);
        $this->subject->offsetSet(100, $correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }

    /**
     * @expectedException \Exception
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function offsetGetThrowsException(): void
    {
        self::expectException(NoIndexFoundException::class);
        self::expectExceptionCode(1_593_714_823);
        self::expectExceptionMessageMatches('/^Index.*100.*Process are not available$/');
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }
}
