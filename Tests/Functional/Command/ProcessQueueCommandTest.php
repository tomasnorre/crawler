<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Command;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Command\ProcessQueueCommand;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Crawler;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessQueueCommandTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');

        $crawlerController = GeneralUtility::makeInstance(CrawlerController::class);

        $command = new ProcessQueueCommand(
            new Crawler(),
            $crawlerController,
            new ProcessRepository(),
            new QueueRepository()
        );
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     * @dataProvider processQueueCommandDataProvider
     */
    public function processQueueCommandTest(array $parameters, string $expectedOutput): void
    {
        $arguments = [];
        if (!empty($parameters)) {
            $arguments = $parameters;
        }

        $this->commandTester->execute($arguments);
        $commandOutput = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedOutput, $commandOutput);
    }

    public function processQueueCommandDataProvider(): iterable
    {
        yield 'No params' => [
            'parameters' => [],
            'expectedOutput' => 'Unprocessed Items remaining:0',
        ];
        yield '--amount 5' => [
            'parameters' => [
                '--amount' => 5,
            ],
            'expectedOutput' => 'Unprocessed Items remaining:3',
        ];
    }
}
