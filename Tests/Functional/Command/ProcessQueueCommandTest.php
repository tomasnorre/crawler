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

use AOE\Crawler\Command\ProcessQueueCommand;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ProcessQueueCommandTest extends AbstractCommandTests
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'version', 'lang'];

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        $this->queueRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(QueueRepository::class);

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
        if (! empty($parameters)) {
            $arguments = $parameters;
        }

        $this->commandTester->execute($arguments);
        $commandOutput = $this->commandTester->getDisplay();

        self::assertContains($expectedOutput, $commandOutput);
    }

    public function processQueueCommandDataProvider(): array
    {
        return [
            'No params' => [
                'parameters' => [],
                'expectedOutput' => 'Unprocessed Items remaining:0',
            ],
            '--amount 5' => [
                'parameters' => [
                    '--amount' => 5,
                ],
                'expectedOutput' => 'Unprocessed Items remaining:3',
            ],
        ];
    }
}
