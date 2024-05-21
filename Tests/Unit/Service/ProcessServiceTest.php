<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Helper\Sleeper\NullSleeper;
use AOE\Crawler\Service\ProcessService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\ProcessService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Helper\Sleeper\NullSleeper::class)]
class ProcessServiceTest extends UnitTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function startProcess(): void
    {
        $mockedProcessRepository = $this->createPartialMock(ProcessRepository::class, ['countNotTimeouted']);

        // This is done to fake that the process is started, the process start itself isn't tested, but the code around is.
        $mockedProcessRepository
            ->expects($this->exactly(2))
            ->method('countNotTimeouted')
            ->will($this->onConsecutiveCalls(1, 2));

        $processService = $this->getAccessibleMock(
            ProcessService::class,
            ['getCrawlerCliPath'],
            [$mockedProcessRepository, new NullSleeper()]
        );

        $processService->expects($this->any())->method('getCrawlerCliPath')->willReturn('php');

        self::assertTrue($processService->startProcess());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function startProcessThrowsProcessException(): void
    {
        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Something went wrong: process did not appear within 10 seconds.');

        $mockedProcessRepository = $this->createPartialMock(ProcessRepository::class, ['countNotTimeouted']);

        // This is done to fake that the process is started, the process start itself isn't tested, but the code around is.
        $mockedProcessRepository
            ->expects($this->exactly(11))
            ->method('countNotTimeouted')
            ->will($this->onConsecutiveCalls(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1));

        $processService = $this->getAccessibleMock(
            ProcessService::class,
            ['getCrawlerCliPath'],
            [$mockedProcessRepository, new NullSleeper()]
        );

        $processService->expects($this->any())->method('getCrawlerCliPath')->willReturn('php');

        self::assertTrue($processService->startProcess());
    }
}
