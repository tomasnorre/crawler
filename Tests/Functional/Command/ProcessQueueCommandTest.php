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

class ProcessQueueCommandTest extends AbstractCommandTests
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
     * @test
     * @dataProvider processQueueCommandDataProvider
     */
    public function processQueueCommandTest(array $parameters, string $expectedOutput): void
    {
        $commandOutput = '';
        $cliCommand = $this->getTypo3TestBinaryCommand() . ' crawler:processqueue ' . implode(' ', $parameters);
        CommandUtility::exec($cliCommand, $commandOutput);

        self::assertContains($expectedOutput, $commandOutput[0]);
    }

    public function processQueueCommandDataProvider(): array
    {
        return [
            'No params' => [
                'parameters' => [],
                'expectedOutput' => 'Unprocessed Items remaining:0',
            ],
            '--amount 5' => [
                'parameters' => ['--amount 5'],
                'expectedOutput' => 'Unprocessed Items remaining:2',
            ],
        ];
    }
}
