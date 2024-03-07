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
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FlushQueueCommandTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Domain\Repository\QueueRepository $queueRepository;

    protected \Symfony\Component\Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBackendRequest();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.csv');
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        $command = new FlushQueueCommand();
        $this->commandTester = new CommandTester($command);
    }

    /**
     * This will test that the commands and output contains what needed, the cleanup it self isn't tested.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('flushQueueDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function flushQueueCommandTest(string $mode, string $expectedOutput, int $expectedCount): void
    {
        $arguments = ['mode' => $mode];
        $this->commandTester->execute($arguments);
        $commandOutput = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedOutput, $commandOutput);
        self::assertEquals($expectedCount, $this->queueRepository->findAll()->count());
    }

    public static function flushQueueDataProvider(): iterable
    {
        yield 'Flush All' => [
            'mode' => 'all',
            'expectedOutput' => 'All entries in Crawler queue have been flushed',
            'expectedCount' => 0,
        ];
        yield 'Flush Pending' => [
            'mode' => 'pending',
            'expectedOutput' => 'All entries in Crawler queue with status "pending" have been flushed',
            'expectedCount' => 7,
        ];
        yield 'Flush Finished' => [
            'mode' => 'finished',
            'expectedOutput' => 'All entries in Crawler queue with status "finished" have been flushed',
            'expectedCount' => 8,
        ];
    }
}
