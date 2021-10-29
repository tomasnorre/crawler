<?php

declare(strict_types=1);

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

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\CrawlStrategy\CrawlStrategyFactory
 * @covers \AOE\Crawler\Configuration\ExtensionConfigurationProvider
 * @covers \AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy
 */
class CrawlStrategyFactoryTest extends UnitTestCase
{
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

        self::assertInstanceOf(
            GuzzleExecutionStrategy::class,
            $crawlStrategy
        );
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

        self::assertInstanceOf(
            SubProcessExecutionStrategy::class,
            $crawlStrategy
        );
    }
}
