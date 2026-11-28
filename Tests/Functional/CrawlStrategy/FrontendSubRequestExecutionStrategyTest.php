<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\CrawlStrategy;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\FrontendSubRequestExecutionStrategy;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Value\QueueEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Frontend\Http\Application;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Crawls pages with direct requests (makeDirectRequests), which render the pages
 * in this process instead of fetching them by HTTP.
 */
#[CoversClass(FrontendSubRequestExecutionStrategy::class)]
class FrontendSubRequestExecutionStrategyTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @noRector
     * @noRector \Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
        ],
    ];

    protected array $coreExtensionsToLoad = ['info', 'seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'crawler' => [
                'makeDirectRequests' => '1',
                'frontendBasePath' => '/',
            ],
        ],
    ];

    private QueueExecutor $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_crawler_queue.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->setUpFrontendRootPage(1, ['EXT:crawler/Tests/Functional/CrawlStrategy/Fixtures/page.typoscript']);
        $this->subject = new QueueExecutor(
            new CrawlStrategyFactory($this->get(ExtensionConfigurationProvider::class), $this->createStrategies()),
            $this->get(EventDispatcher::class),
            $this->get(CallbackExecutionStrategy::class),
        );
    }

    #[Test]
    public function directRequestsUseFrontendSubRequests(): void
    {
        $crawlStrategy = (new \ReflectionProperty(QueueExecutor::class, 'crawlStrategy'))
            ->getValue($this->get(QueueExecutor::class));

        self::assertInstanceOf(FrontendSubRequestExecutionStrategy::class, $crawlStrategy);
    }

    #[Test]
    public function crawlsPagesOneAfterAnotherWithTheirUserGroups(): void
    {
        $workerUser = new UserAspect();
        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $workerUser);

        $first = $this->crawl(2001);
        $second = $this->crawl(2002);

        self::assertTrue($first['running']);
        self::assertSame(1, $first['vars']['id']);
        self::assertSame('0,-2,1', $first['vars']['gr_list']);
        self::assertSame(['User Groups: 1'], $first['log']);
        self::assertSame(2, $second['vars']['id']);
        self::assertSame('0,-2,1,2', $second['vars']['gr_list']);
        self::assertSame($workerUser, $context->getAspect('frontend.user'));
        self::assertArrayNotHasKey('TYPO3_REQUEST', $GLOBALS);
    }

    #[Test]
    public function reportsPageNotFound(): void
    {
        self::assertSame([
            'errorlog' => ['404 Not Found'],
        ], $this->crawl(2003));
    }

    /**
     * The frontend of the testing framework needs its request context, which the
     * InternalRequest of a frontend functional test brings along otherwise.
     */
    private function createStrategies(): ContainerInterface
    {
        $application = new class($this->get(Application::class)) extends Application {
            public function __construct(
                private readonly Application $application
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->application->handle(
                    $request->withAttribute('typo3.testing.context', new InternalRequestContext())
                );
            }
        };
        $strategy = new FrontendSubRequestExecutionStrategy(
            $application,
            $this->get(Context::class),
            $this->get(PageRenderer::class),
            $this->get(ExtensionConfigurationProvider::class),
            $this->get(ClockInterface::class),
        );

        $strategies = $this->createStub(ContainerInterface::class);
        $strategies->method('get')->willReturn($strategy);
        return $strategies;
    }

    private function crawl(int $queueId): array
    {
        $queueItem = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_crawler_queue')
            ->select(['*'], 'tx_crawler_queue', [
                'qid' => $queueId,
            ])
            ->fetchAssociative();
        self::assertIsArray($queueItem);

        $content = $this->subject->executeQueueItem(QueueEntry::fromRow($queueItem))->getContent();

        self::assertIsArray($content);
        return $content;
    }
}
