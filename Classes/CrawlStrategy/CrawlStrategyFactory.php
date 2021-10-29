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

namespace AOE\Crawler\CrawlStrategy;

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
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

        if ($extensionSettings['makeDirectRequests'] ?? false) {
            /** @var CrawlStrategy $instance */
            $instance = GeneralUtility::makeInstance(SubProcessExecutionStrategy::class);
        } else {
            $instance = GeneralUtility::makeInstance(GuzzleExecutionStrategy::class);
        }

        return $instance;
    }
}
