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

use AOE\Crawler\Service\ProcessService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlushQueueCommandTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
    }

    /**
     * This will test that the commands and output contains what needed, the cleanup it self isn't tested.
     *
     * @test
     * @dataProvider flushQueueDataProvider
     */
    public function flushQueueCommandTest(string $mode, string $expected): void
    {
        $commandOutput = '';
        $cliCommand = self::getTypo3TestBinaryCommand() . ' crawler:flushQueue ' . $mode;
        CommandUtility::exec($cliCommand, $commandOutput);

        self::assertContains($expected, $commandOutput);
    }

    public function flushQueueDataProvider(): array
    {
        return [
            'Flush All' => [
                'mode' => 'all',
                'expected' => 'All entries in Crawler queue will be flushed'
            ],
            'Flush Pending' => [
                'mode' => 'pending',
                'expected' => 'All entries in Crawler queue, with status: "pending" will be flushed',
            ],
            'Flush Finished' => [
                'mode' => 'finished',
                'expected' => 'All entries in Crawler queue, with status: "finished" will be flushed',
            ],
            'Unknown mode' => [
                'mode' => 'unknown',
                'expected' => 'No matching parameters found.',
            ]
        ];
    }

    private function getTypo3TestBinaryCommand(): string
    {
        $processService = GeneralUtility::makeInstance(ProcessService::class);
        $cliPath = substr($processService->getCrawlerCliPath(), 0, -21);

        return $cliPath;


    }
}
