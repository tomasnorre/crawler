<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Configuration;

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
use AOE\Crawler\Configuration\ExtensionSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExtensionConfigurationProvider::class)]
class ExtensionConfigurationProviderTest extends UnitTestCase
{
    #[Test]
    public function getExtensionSettingsReturnsSettingsOfExtensionConfiguration(): void
    {
        $this->addExtensionConfiguration([
            'crawlHiddenPages' => '1',
            'maxCompileUrls' => '42',
        ]);

        self::assertEquals(
            new ExtensionSettings(crawlHiddenPages: true, maxCompileUrls: 42),
            (new ExtensionConfigurationProvider())->getExtensionSettings()
        );
    }

    #[Test]
    public function getExtensionSettingsReturnsDefaultsIfConfigurationIsNoArray(): void
    {
        $this->addExtensionConfiguration('1');

        self::assertEquals(new ExtensionSettings(), (new ExtensionConfigurationProvider())->getExtensionSettings());
    }

    public static function configurationExceptionDataProvider(): iterable
    {
        yield 'extension not configured' => [
            'exception' => new ExtensionConfigurationExtensionNotConfiguredException('Not configured', 1_764_300_000),
        ];
        yield 'path does not exist' => [
            'exception' => new ExtensionConfigurationPathDoesNotExistException('Not configured', 1_764_300_001),
        ];
    }

    #[DataProvider('configurationExceptionDataProvider')]
    #[Test]
    public function getExtensionSettingsLogsErrorAndReturnsDefaultsIfExtensionIsNotConfigured(
        \Exception $exception
    ): void {
        $this->addFailingExtensionConfiguration($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Not configured');
        $subject = new ExtensionConfigurationProvider();
        $subject->setLogger($logger);

        self::assertEquals(new ExtensionSettings(), $subject->getExtensionSettings());
    }

    #[Test]
    public function getExtensionSettingsWithoutLoggerReturnsDefaultsIfExtensionIsNotConfigured(): void
    {
        $this->addFailingExtensionConfiguration(
            new ExtensionConfigurationExtensionNotConfiguredException('Not configured', 1_764_300_002)
        );

        self::assertEquals(new ExtensionSettings(), (new ExtensionConfigurationProvider())->getExtensionSettings());
    }

    private function addFailingExtensionConfiguration(\Exception $exception): void
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willThrowException($exception);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);
    }

    private function addExtensionConfiguration(mixed $configuration): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->expects(self::once())->method('get')->with('crawler')->willReturn($configuration);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration);
    }
}
