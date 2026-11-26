<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Event\ModifySkipPageEvent;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class PageService
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly PageRootlineProvider $pageRootlineProvider,
    ) {
    }

    /**
     * Check if the given page should be crawled
     *
     * @return false|string false if the page should be crawled (not excluded), true / skipMessage if it should be skipped
     */
    public function checkIfPageShouldBeSkipped(array $pageRow): false|string
    {
        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();

        // if page is hidden
        if (!($extensionSettings['crawlHiddenPages'] ?? false) && ($pageRow['hidden'] ?? false)) {
            return 'Because page is hidden';
        }

        if (in_array($pageRow['doktype'], $this->getDisallowedDokTypes(), true)) {
            return sprintf('Because doktype "%d" is not allowed', $pageRow['doktype']);
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] ?? [] as $key => $doktypeList) {
            if (GeneralUtility::inList($doktypeList, $pageRow['doktype'])) {
                return sprintf(
                    'Doktype "%d" was excluded by excludeDoktype configuration key "%s"',
                    $pageRow['doktype'],
                    $key
                );
            }
        }

        $exclusionReason = $this->getExclusionReason((int) ($pageRow['uid'] ?? 0));
        if ($exclusionReason !== false) {
            return $exclusionReason;
        }

        $event = $this->eventDispatcher->dispatch(new ModifySkipPageEvent($pageRow));
        return $event->isSkipped();
    }

    /**
     * Checks the "Exclude from crawling" page properties of the page and its parent pages.
     * A page is excluded if it is excluded itself, or if a parent page excludes its subpages too.
     */
    private function getExclusionReason(int $pageId): false|string
    {
        if ($pageId === 0) {
            return false;
        }

        foreach ($this->pageRootlineProvider->getRootlineWithExclusionFields($pageId) as $page) {
            if ($page['uid'] === $pageId) {
                if ($page[PageRootlineProvider::FIELD_EXCLUDE]) {
                    return 'Because page is excluded from crawling';
                }
                continue;
            }
            if ($page[PageRootlineProvider::FIELD_EXCLUDE] && $page[PageRootlineProvider::FIELD_EXCLUDE_SUBPAGES]) {
                return sprintf('Because parent page "%d" is excluded from crawling with its subpages', $page['uid']);
            }
        }
        return false;
    }

    private function getDisallowedDokTypes(): array
    {
        return [
            PageRepository::DOKTYPE_LINK,
            PageRepository::DOKTYPE_SHORTCUT,
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_BE_USER_SECTION,
        ];
    }
}
