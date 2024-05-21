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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Utility\PhpBinaryUtility;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 */
abstract class AbstractBackendModuleController
{
    protected bool $isErrorDetected = false;
    protected int $pageUid;
    protected ModuleTemplate $moduleTemplate;
    protected array $extensionSettings = [];

    #[Required]
    public function setExtensionSettings(): void
    {
        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $this->extensionSettings = $configurationProvider->getExtensionConfiguration();
    }

    protected function setupView(ServerRequestInterface $request, int $pageUid): ModuleTemplate
    {
        $moduleTemplate = (GeneralUtility::makeInstance(ModuleTemplateFactory::class))->create($request);
        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($pageUid, $permissionClause);
        if ($pageRecord) {
            $moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }

        return $moduleTemplate;
    }

    protected function makeCrawlerProcessableChecks(array $extensionSettings): void
    {
        if (!$this->isPhpForkAvailable()) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage(
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.noPhpForkAvailable'
                )
            );
        }

        $exitCode = 0;
        $out = [];
        CommandUtility::exec(PhpBinaryUtility::getPhpBinary() . ' -v', $out, $exitCode);
        if ($exitCode > 0) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage(
                sprintf($this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.phpBinaryNotFound'
                ), htmlspecialchars((string) $extensionSettings['phpPath'], ENT_QUOTES | ENT_HTML5))
            );
        }
    }

    protected function getModuleMenu(): array
    {
        return [
            'logDepth' => [
                0 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
                ),
                1 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'
                ),
                2 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'
                ),
                3 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'
                ),
                4 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'
                ),
                99 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'
                ),
            ],
            'crawlaction' => [
                'start' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.start'
                ),
                'log' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.log'
                ),
                'multiprocess' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.multiprocess'
                ),
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'displayLog' => [
                'all' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.all'
                ),
                'pending' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.pending'
                ),
                'finished' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.finished'
                ),
            ],
            'itemsPerPage' => [
                '5' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.5'
                ),
                '10' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.10'
                ),
                '50' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.50'
                ),
                '0' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.0'
                ),
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

    protected function getParsedBody(ServerRequestInterface $request): array
    {
        if (is_array($request->getParsedBody())) {
            return $request->getParsedBody();
        }

        return [];
    }

    /**
     * Indicate that the required PHP method "popen" is
     * available in the system.
     */
    private function isPhpForkAvailable(): bool
    {
        return function_exists('popen');
    }
}
