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

use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Utility\CommandUtility;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->queueRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(QueueRepository::class);
    }

    /**
     * This will test that the commands and output contains what needed, the cleanup it self isn't tested.
     *
     * @test
     * @dataProvider flushQueueDataProvider
     */
    public function flushQueueCommandTest(string $mode, string $expectedOutput, int $expectedCount): void
    {
        $commandOutput = '';
        $cliCommand = $this->getTypo3TestBinaryCommand() . ' crawler:flushQueue ' . $mode;
        CommandUtility::exec($cliCommand, $commandOutput);

        self::assertContains($expectedOutput, $commandOutput);
        self::assertEquals(
            $expectedCount,
            $this->queueRepository->findAll()->count()
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
                'expectedCount' => 8,
            ],
            'Unknown mode' => [
                'mode' => 'unknown',
                'expectedOutput' => 'No matching parameters found.',
                'expectedCount' => 15,
            ],
        ];
    }
}
