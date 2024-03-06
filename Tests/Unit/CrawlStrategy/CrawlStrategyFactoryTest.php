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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\CrawlStrategy\CrawlStrategyFactory
 * @covers \AOE\Crawler\Configuration\ExtensionConfigurationProvider
 * @covers \AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy
 */
class CrawlStrategyFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function crawlerStrategyFactoryReturnsGuzzleExecutionStrategyAsItIsDefault(): void
    {
        $configuration = [
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $crawlStrategy = GeneralUtility::makeInstance(CrawlStrategyFactory::class)->create();

        self::assertInstanceOf(GuzzleExecutionStrategy::class, $crawlStrategy);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
