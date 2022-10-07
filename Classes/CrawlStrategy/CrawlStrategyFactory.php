<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CrawlStrategyFactory
{
    private ExtensionConfigurationProvider $configurationProvider;

    public function __construct(?ExtensionConfigurationProvider $configurationProvider = null)
    {
        $this->configurationProvider = $configurationProvider ?? GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
    }

    public function create(): CrawlStrategyInterface
    {
        $settings = $this->configurationProvider->getExtensionConfiguration();
        $extensionSettings = is_array($settings) ? $settings : [];

        if ($extensionSettings['makeDirectRequests'] ?? false) {
            /** @var CrawlStrategyInterface $instance */
            $instance = GeneralUtility::makeInstance(SubProcessExecutionStrategy::class, $this->configurationProvider);
        } else {
            $instance = GeneralUtility::makeInstance(GuzzleExecutionStrategy::class, $this->configurationProvider);
        }

        return $instance;
    }
}
