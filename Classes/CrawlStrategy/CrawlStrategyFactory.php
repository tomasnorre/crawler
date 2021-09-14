<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\CrawlStrategy;

use TomasNorre\Crawler\Configuration\ExtensionConfigurationProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CrawlStrategyFactory
{
    /**
     * @var ExtensionConfigurationProvider
     */
    private $configurationProvider;

    public function __construct(?ExtensionConfigurationProvider $configurationProvider = null)
    {
        $this->configurationProvider = $configurationProvider ?? GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
    }

    public function create(): CrawlStrategy
    {
        $settings = $this->configurationProvider->getExtensionConfiguration();
        $extensionSettings = is_array($settings) ? $settings : [];

        if ($extensionSettings['makeDirectRequests']) {
            /** @var CrawlStrategy $instance */
            $instance = GeneralUtility::makeInstance(SubProcessExecutionStrategy::class);
        } else {
            $instance = GeneralUtility::makeInstance(GuzzleExecutionStrategy::class);
        }

        return $instance;
    }
}
