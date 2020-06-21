<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Command;

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

use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class FlushQueueCommandTest extends AbstractCommandTests
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'fluid'];

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
        $this->crawlerController = $objectManager->get(CrawlerController::class);

        $command = new FlushQueueCommand($this->crawlerController);
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

        self::assertContains($expectedOutput, $commandOutput);
        self::assertEquals(
            $expectedCount,
            $this->queueRepository->countAll()
        );
    }

    public function flushQueueDataProvider(): array
    {
        return [
            'Flush All' => [
                'mode' => 'all',
                'expectedOutput' => 'All entries in Crawler queue will be flushed',
                'expectedCount' => 0,
            ],
            'Flush Pending' => [
                'mode' => 'pending',
                'expectedOutput' => 'All entries in Crawler queue, with status: "pending" will be flushed',
                'expectedCount' => 7,
            ],
            'Flush Finished' => [
                'mode' => 'finished',
                'expectedOutput' => 'All entries in Crawler queue, with status: "finished" will be flushed',
                'expectedCount' => 7,
            ],
            'Unknown mode' => [
                'mode' => 'unknown',
                'expectedOutput' => 'No matching parameters found.',
                'expectedCount' => 14,
            ],
        ];
    }
}
