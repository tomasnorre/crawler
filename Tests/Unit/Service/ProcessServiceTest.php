<?php

declare(strict_types=1);

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

namespace AOE\Crawler\Tests\Unit\Service;

use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ProcessServiceTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 * @covers \AOE\Crawler\Service\ProcessService
 */
class ProcessServiceTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var ProcessService
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->createPartialMock(ProcessService::class, ['getCrawlerCliPath']);
        $this->subject->expects($this->any())->method('getCrawlerCliPath')->willReturn('php');
    }

    /**
     * @test
     */
    public function startProcess(): void
    {
        $mockedProcessRepository = $this->createPartialMock(ProcessRepository::class, ['countNotTimeouted']);
        // This is done to fake that the process is started, the process start itself isn't tested, but the code around it is.
        $mockedProcessRepository->expects($this->exactly(2))->method('countNotTimeouted')->will($this->onConsecutiveCalls(1, 2));
        $this->subject->setProcessRepository($mockedProcessRepository);

        self::assertTrue($this->subject->startProcess());
    }
}
