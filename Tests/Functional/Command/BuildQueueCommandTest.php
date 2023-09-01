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

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Tests\Functional\LanguageServiceTestTrait;
use AOE\Crawler\Tests\Functional\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BuildQueueCommandTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    use LanguageServiceTestTrait;
    use SiteBasedTestTrait;

    /**
     * @noRector
     * @noRector \Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
    ];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected QueueRepository $queueRepository;

    protected CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBackendRequest();
        $this->setupBackendUser(0);
        $this->setupLanguageService();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );

        $command = new BuildQueueCommand();
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     * @dataProvider buildQueueCommandDataProvider
     */
    public function buildQueueCommandTest(array $parameters, string $expectedOutput, int $expectedCount): void
    {
        $arguments = [];
        if (!empty($parameters)) {
            $arguments = $parameters;
        }

        $this->commandTester->execute($arguments);

        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
        self::assertEquals($expectedCount, $this->queueRepository->findAll()->count());
    }

    public static function buildQueueCommandDataProvider(): iterable
    {
        $crawlerConfiguration = 'default';

        yield 'Start page 1' => [
            'parameters' => [
                'page' => 1,
                'conf' => $crawlerConfiguration,
            ],
            'expectedOutput' => 'Putting 1 entries in queue:',
            'expectedCount' => 1,
        ];
        yield 'Start page 1, depth 99' => [
            'parameters' => [
                'page' => 1,
                'conf' => $crawlerConfiguration,
                '--depth' => 99,
            ],
            'expectedOutput' => 'Putting 9 entries in queue:',
            'expectedCount' => 9,
        ];
        yield 'Start page 1, --mode queue (default)' => [
            'parameters' => [
                'page' => 1,
                'conf' => $crawlerConfiguration,
            ],
            'expectedOutput' => 'Putting 1 entries in queue:',
            'expectedCount' => 1,
        ];
        yield 'Start page 1, --mode url' => [
            'parameters' => [
                'page' => 1,
                'conf' => $crawlerConfiguration,
                '--mode' => 'url',
            ],
            'expectedOutput' => 'https://www.example.com/',
            'expectedCount' => 0,
        ];
        yield 'Start page 1,  --mode exec' => [
            'parameters' => [
                'page' => 1,
                'conf' => $crawlerConfiguration,
                '--mode' => 'exec',
            ],
            'expectedOutput' => 'Executing 1 requests right away:',
            'expectedCount' => 1,
        ];
        yield 'Start page 0, expecting error' => [
            'parameters' => [
                'page' => 0,
                'conf' => $crawlerConfiguration,
                '--mode' => 'queue',
            ],
            'expectedOutput' => 'Page 0 is not a valid page, please check you root page id and try again.',
            'expectedCount' => 0,
        ];
    }

    protected function setupBackendUser(int $userUid): BackendUserAuthentication
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['BE_USER']->method('isInWebMount')->willReturn(true);

        return $GLOBALS['BE_USER'];
    }
}
