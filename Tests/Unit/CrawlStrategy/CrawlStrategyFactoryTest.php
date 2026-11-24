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
use AOE\Crawler\CrawlStrategy\FrontendSubRequestExecutionStrategy;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\Interface\CrawlStrategyInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CrawlStrategyFactory::class)]
class CrawlStrategyFactoryTest extends UnitTestCase
{
    public static function createReturnsStrategyOfSettingsDataProvider(): iterable
    {
        yield 'direct requests disabled' => [
            'settings' => [
                'makeDirectRequests' => '0',
            ],
            'expected' => GuzzleExecutionStrategy::class,
        ];
        yield 'direct requests enabled' => [
            'settings' => [
                'makeDirectRequests' => '1',
            ],
            'expected' => FrontendSubRequestExecutionStrategy::class,
        ];
        yield 'direct requests not configured' => [
            'settings' => [],
            'expected' => GuzzleExecutionStrategy::class,
        ];
    }

    #[DataProvider('createReturnsStrategyOfSettingsDataProvider')]
    #[Test]
    public function createReturnsStrategyOfSettings(array $settings, string $expected): void
    {
        $configurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $configurationProvider->method('getExtensionConfiguration')->willReturn($settings);

        $strategy = $this->createStub(CrawlStrategyInterface::class);
        $strategies = $this->createMock(ContainerInterface::class);
        $strategies->expects(self::once())->method('get')->with($expected)->willReturn($strategy);

        $subject = new CrawlStrategyFactory($configurationProvider, $strategies);

        self::assertSame($strategy, $subject->create());
    }
}
