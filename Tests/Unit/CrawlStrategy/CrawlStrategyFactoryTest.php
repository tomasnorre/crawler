<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\CrawlStrategyFactory::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Configuration\ExtensionConfigurationProvider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy::class)]
class CrawlStrategyFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function crawlerStrategyFactoryReturnsGuzzleExecutionStrategy(): void
    {
        $configuration = [
            'makeDirectRequests' => 0,
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $crawlStrategy = GeneralUtility::makeInstance(CrawlStrategyFactory::class)->create();

        self::assertInstanceOf(GuzzleExecutionStrategy::class, $crawlStrategy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function crawlerStrategyFactoryReturnsSubProcessExecutionStrategy(): void
    {
        $configuration = [
            'makeDirectRequests' => 1,
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $crawlStrategy = GeneralUtility::makeInstance(CrawlStrategyFactory::class)->create();

        self::assertInstanceOf(SubProcessExecutionStrategy::class, $crawlStrategy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function crawlerStrategyFactoryReturnsGuzzleExecutionStrategyAsItIsDefault(): void
    {
        $configuration = [
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $crawlStrategy = GeneralUtility::makeInstance(CrawlStrategyFactory::class)->create();

        self::assertInstanceOf(GuzzleExecutionStrategy::class, $crawlStrategy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function crawlerStrategyFactoryConstructedWithConfigurationProviderReturnsSubProcess(): void
    {
        $configuration = [
            'makeDirectRequests' => 1,
            'frontendBasePath' => '/',
        ];

        $extensionConfigurationProvider = $this->prophesize(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->getExtensionConfiguration()->willReturn($configuration);
        $crawlStrategy = GeneralUtility::makeInstance(
            CrawlStrategyFactory::class,
            $extensionConfigurationProvider->reveal()
        )->create();

        self::assertInstanceOf(SubProcessExecutionStrategy::class, $crawlStrategy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function crawlerStrategyFactoryConstructedWithConfigurationProviderReturnsGuzzle(): void
    {
        $configuration = [
            'makeDirectRequests' => 0,
            'frontendBasePath' => '/',
        ];

        $extensionConfigurationProvider = $this->prophesize(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->getExtensionConfiguration()->willReturn($configuration);
        $crawlStrategy = GeneralUtility::makeInstance(
            CrawlStrategyFactory::class,
            $extensionConfigurationProvider->reveal()
        )->create();

        self::assertInstanceOf(GuzzleExecutionStrategy::class, $crawlStrategy);
    }
}
