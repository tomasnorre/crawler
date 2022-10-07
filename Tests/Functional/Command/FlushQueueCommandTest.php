<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Command;

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

use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlushQueueCommandTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Domain\Repository\QueueRepository $queueRepository;

    protected \Symfony\Component\Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        $command = new FlushQueueCommand();
        $this->commandTester = new CommandTester($command);
    }

    /**
     * This will test that the commands and output contains what needed, the cleanup it self isn't tested.
     *
     * @test
     * @dataProvider flushQueueDataProvider
     */
    public function flushQueueCommandTest(string $mode, string $expectedOutput, int $expectedCount): void
    {
        $arguments = ['mode' => $mode];
        $this->commandTester->execute($arguments);
        $commandOutput = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedOutput, $commandOutput);
        self::assertEquals(
            $expectedCount,
            $this->queueRepository->findAll()->count()
        );
    }

    public function flushQueueDataProvider(): iterable
    {
        yield 'Flush All' => [
            'mode' => 'all',
            'expectedOutput' => 'All entries in Crawler queue will be flushed',
            'expectedCount' => 0,
        ];
        yield 'Flush Pending' => [
            'mode' => 'pending',
            'expectedOutput' => 'All entries in Crawler queue, with status: "pending" will be flushed',
            'expectedCount' => 7,
        ];
        yield 'Flush Finished' => [
            'mode' => 'finished',
            'expectedOutput' => 'All entries in Crawler queue, with status: "finished" will be flushed',
            'expectedCount' => 8,
        ];
    }
}
