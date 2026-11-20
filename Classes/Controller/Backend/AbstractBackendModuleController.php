<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

/*
 * (c) 2022-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 */
abstract class AbstractBackendModuleController
{
    protected int $pageUid;
    protected ModuleTemplate $moduleTemplate;

    protected function setupView(ServerRequestInterface $request, int $pageUid): ModuleTemplate
    {
        $moduleTemplate = (GeneralUtility::makeInstance(ModuleTemplateFactory::class))->create($request);
        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($pageUid, $permissionClause);
        if ($pageRecord) {
            $moduleTemplate->getDocHeaderComponent()->setPageBreadcrumb($pageRecord);
        }

        return $moduleTemplate;
    }

    protected function getModuleMenu(): array
    {
        return [
            'logDepth' => [
                0 => $this->getLanguageService()->sL('core.core:labels.depth_0'),
                1 => $this->getLanguageService()->sL('core.core:labels.depth_1'),
                2 => $this->getLanguageService()->sL('core.core:labels.depth_2'),
                3 => $this->getLanguageService()->sL('core.core:labels.depth_3'),
                4 => $this->getLanguageService()->sL('core.core:labels.depth_4'),
                99 => $this->getLanguageService()->sL('core.core:labels.depth_infi'),
            ],
            'displayLog' => [
                'all' => $this->getLanguageService()->sL('crawler.messages:labels.all'),
                'pending' => $this->getLanguageService()->sL('crawler.messages:labels.pending'),
                'finished' => $this->getLanguageService()->sL('crawler.messages:labels.finished'),
            ],
            'itemsPerPage' => [
                '5' => $this->getLanguageService()->sL('crawler.messages:labels.itemsPerPage.5'),
                '10' => $this->getLanguageService()->sL('crawler.messages:labels.itemsPerPage.10'),
                '50' => $this->getLanguageService()->sL('crawler.messages:labels.itemsPerPage.50'),
                '0' => $this->getLanguageService()->sL('crawler.messages:labels.itemsPerPage.0'),
            ],
        ];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
