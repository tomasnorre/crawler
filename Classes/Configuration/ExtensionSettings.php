<?php

declare(strict_types=1);

namespace AOE\Crawler\Configuration;

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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * The extension settings of the crawler, with the defaults of ext_conf_template.txt
 *
 * @internal
 */
final readonly class ExtensionSettings
{
    public const DEFAULT_MAX_COMPILE_URLS = 10_000;
    private const MAX_COMPILE_URLS_LIMIT = 1_000_000_000;

    /**
     * @param string $frontendBasePath Base path of the website frontend for direct requests, fx. "/cms/"
     * @param bool $makeDirectRequests Render the pages within the worker process instead of requesting them via HTTP
     * @param int $maxCompileUrls Maximal number of URLs which can be added to the queue at one time
     * @param bool $enableTimeslot Check for duplicate queue entries within a timeslot around the scheduled time
     * @param bool $cleanUpOldQueueEntries Delete old queue entries when adding new ones from the CLI
     * @param int $cleanUpProcessedAge Days after which processed queue entries are deleted
     * @param int $cleanUpScheduledAge Days after which scheduled queue entries are deleted
     */
    public function __construct(
        public string $frontendBasePath = '/',
        public bool $crawlHiddenPages = false,
        public bool $makeDirectRequests = false,
        public int $maxCompileUrls = self::DEFAULT_MAX_COMPILE_URLS,
        public bool $enableTimeslot = true,
        public bool $cleanUpOldQueueEntries = true,
        public int $cleanUpProcessedAge = 2,
        public int $cleanUpScheduledAge = 7,
    ) {
    }

    /**
     * Creates the settings from the array of the extension configuration, missing or invalid values get their default
     *
     * @param array<array-key, mixed> $settings
     */
    public static function fromArray(array $settings): self
    {
        $defaults = new self();
        return new self(
            frontendBasePath: self::getString($settings, 'frontendBasePath', $defaults->frontendBasePath),
            crawlHiddenPages: self::getBool($settings, 'crawlHiddenPages', $defaults->crawlHiddenPages),
            makeDirectRequests: self::getBool($settings, 'makeDirectRequests', $defaults->makeDirectRequests),
            maxCompileUrls: MathUtility::forceIntegerInRange(
                $settings['maxCompileUrls'] ?? self::DEFAULT_MAX_COMPILE_URLS,
                1,
                self::MAX_COMPILE_URLS_LIMIT,
                self::DEFAULT_MAX_COMPILE_URLS
            ),
            enableTimeslot: self::getBool($settings, 'enableTimeslot', $defaults->enableTimeslot),
            cleanUpOldQueueEntries: self::getBool(
                $settings,
                'cleanUpOldQueueEntries',
                $defaults->cleanUpOldQueueEntries
            ),
            cleanUpProcessedAge: self::getInt($settings, 'cleanUpProcessedAge', $defaults->cleanUpProcessedAge),
            cleanUpScheduledAge: self::getInt($settings, 'cleanUpScheduledAge', $defaults->cleanUpScheduledAge),
        );
    }

    /**
     * @param array<array-key, mixed> $settings
     */
    private static function getString(array $settings, string $key, string $default): string
    {
        $value = $settings[$key] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<array-key, mixed> $settings
     */
    private static function getBool(array $settings, string $key, bool $default): bool
    {
        $value = $settings[$key] ?? null;
        return is_scalar($value) ? (bool) $value : $default;
    }

    /**
     * @param array<array-key, mixed> $settings
     */
    private static function getInt(array $settings, string $key, int $default): int
    {
        $value = $settings[$key] ?? null;
        return is_numeric($value) ? (int) $value : $default;
    }
}
