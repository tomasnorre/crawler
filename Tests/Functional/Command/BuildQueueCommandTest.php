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

class BuildQueueCommandTest extends AbstractCommandTests
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
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.xml');
        $this->queueRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(QueueRepository::class);
    }

    /**
     * @test
     * @dataProvider buildQueueCommandDataProvider
     */
    public function buildQueueCommandTest(array $parameters, string $expectedOutput, int $expectedCount): void
    {
        $commandOutput = '';
        $cliCommand = $this->getTypo3TestBinaryCommand() . ' crawler:buildQueue ' . implode(' ', $parameters);
        CommandUtility::exec($cliCommand, $commandOutput);

        self::assertContains($expectedOutput, $commandOutput);
        self::assertEquals(
            $expectedCount,
            $this->queueRepository->findAll()->count()
        );
    }

    public function buildQueueCommandDataProvider(): array
    {
        $crawlerConfiguration = 'default';

        return [
            'Start page 1' => [
                'parameters' => [1, $crawlerConfiguration],
                'expectedOutput' => 'Putting 1 entries in queue:',
                'expectedCount' => 1,
            ],
            'Start page 1, depth 99' => [
                'parameters' => [1, $crawlerConfiguration, '--depth 99'],
                'expectedOutput' => 'Putting 9 entries in queue:',
                'expectedCount' => 9,
            ],
            'Start page 1, --mode queue (default)' => [
                'parameters' => [1, $crawlerConfiguration],
                'expectedOutput' => 'Putting 1 entries in queue:',
                'expectedCount' => 1,
            ],
            'Start page 1, --mode url' => [
                'parameters' => [1, $crawlerConfiguration, '--mode url'],
                'expectedOutput' => 'https://www.example.com/index.php?id=1&cHash=',
                'expectedCount' => 0,
            ],
            'Start page 1,  --mode exec' => [
                'parameters' => [1, $crawlerConfiguration, '--mode exec'],
                'expectedOutput' => 'Executing 1 requests right away:',
                'expectedCount' => 1,
            ],
        ];
    }
}
