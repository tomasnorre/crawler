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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ProcessCollectionTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
#[CoversClass(ProcessCollection::class)]
#[CoversClass(Process::class)]
class ProcessCollectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected ProcessCollection $subject;

    protected function setUp(): void
    {
        $this->subject = new ProcessCollection();
    }

    #[Test]
    public function getProcessIdsReturnsArray(): void
    {
        /** @var Process $processOne */
        $processOne = $this->getMockBuilder(Process::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $processOne->setProcessId('11');

        /** @var Process $processTwo */
        $processTwo = $this->getMockBuilder(Process::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $processTwo->setProcessId('13');

        $processes = [];
        $processes[] = $processOne;
        $processes[] = $processTwo;

        $collection = new ProcessCollection($processes);

        self::assertEquals(['11', '13'], $collection->getProcessIds());
    }

    #[Test]
    public function appendThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1_593_714_821);
        $wrongObjectType = new \stdClass();
        $this->subject->append($wrongObjectType);
    }

    #[Test]
    public function appendCrawlerDomainObject(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);
        $this->subject->append($correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(0));
    }

    #[Test]
    public function offsetSetThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1_593_714_822);
        $wrongObjectType = new \stdClass();
        $this->subject->offsetSet(100, $wrongObjectType);
    }

    #[Test]
    public function offsetSetAndGet(): void
    {
        /** @var MockObject|Process $correctObjectType */
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);
        $this->subject->offsetSet(100, $correctObjectType);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }

    #[Test]
    public function offsetGetThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectException(NoIndexFoundException::class);
        $this->expectExceptionCode(1_593_714_823);
        $this->expectExceptionMessageMatches('/^Index.*100.*Process are not available$/');
        $correctObjectType = $this->getAccessibleMock(Process::class, [], [], '', false);

        self::assertEquals($correctObjectType, $this->subject->offsetGet(100));
    }
}
