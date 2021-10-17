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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class PageService
{
    /**
     * Check if the given page should be crawled
     *
     * @return false|string false if the page should be crawled (not excluded), true / skipMessage if it should be skipped
     */
    public function checkIfPageShouldBeSkipped(array $pageRow)
    {
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();

        // if page is hidden
        if (! $extensionSettings['crawlHiddenPages'] && $pageRow['hidden']) {
            return 'Because page is hidden';
        }

        if (in_array($pageRow['doktype'], $this->getDisallowedDokTypes(), true)) {
            return 'Because doktype is not allowed';
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] ?? [] as $key => $doktypeList) {
            if (GeneralUtility::inList($doktypeList, $pageRow['doktype'])) {
                return 'Doktype was excluded by "' . $key . '"';
            }
        }

        // veto hook
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] ?? [] as $key => $func) {
            $params = [
                'pageRow' => $pageRow,
            ];
            // expects "false" if page is ok and "true" or a skipMessage if this page should _not_ be crawled
            $veto = GeneralUtility::callUserFunction($func, $params, $this);
            if ($veto !== false) {
                if (is_string($veto)) {
                    return $veto;
                }
                return 'Veto from hook "' . htmlspecialchars($key) . '"';
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
            PageRepository::DOKTYPE_RECYCLER,
        ];
    }
}
