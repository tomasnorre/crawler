<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional;

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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 */
trait SiteBasedTestTrait
{
    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ): void {
        $configuration = $site;
        if (! empty($languages)) {
            $configuration['languages'] = $languages;
        }
        if (! empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }

        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $siteConfiguration = new SiteConfiguration($this->getInstancePath() . '/typo3conf/sites/', $eventDispatcher);

        try {
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    protected function mergeSiteConfiguration(string $identifier, array $overrides): void
    {
        $siteConfiguration = new SiteConfiguration($this->getInstancePath() . '/typo3conf/sites/');
        $configuration = $siteConfiguration->load($identifier);
        $configuration = array_merge($configuration, $overrides);
        try {
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Throwable $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    protected function buildSiteConfiguration(int $rootPageId, string $base = ''): array
    {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }

    protected function buildDefaultLanguageConfiguration(string $identifier, string $base): array
    {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['typo3Language'] = 'default';
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    /**
     * @param string $fallbackType
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        ?string $fallbackType = null
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'base' => $base,
            'locale' => $preset['locale'],
            'iso-639-1' => $preset['iso'],
            'hreflang' => $preset['hrefLang'],
            'direction' => $preset['direction'],
            'typo3Language' => $preset['iso'],
            'flag' => $preset['iso'],
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];

        if (! empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    protected function resolveLanguagePreset(string $identifier)
    {
        if (! isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(sprintf('Undefined preset identifier "%s"', $identifier), 1_533_893_665);
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }
}
