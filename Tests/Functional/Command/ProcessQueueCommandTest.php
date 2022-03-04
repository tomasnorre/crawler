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
use Symfony\Component\Console\Tester\CommandTester;

class ProcessQueueCommandTest extends AbstractCommandTests
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var CommandTester
     */
    protected $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');

        $command = new ProcessQueueCommand();
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
