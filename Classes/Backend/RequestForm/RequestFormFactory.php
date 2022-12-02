<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Helper\Sleeper\SystemSleeper;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Value\CrawlAction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

/**
 * @internal since v12.0.0
 */
final class RequestFormFactory
{
    public static function create(
        CrawlAction $selectedAction,
        StandaloneView $view,
        InfoModuleController $infoModuleController,
        array $extensionSettings,
        array $backendModuleMenu
    ): RequestFormInterface {
        switch ($selectedAction->__toString()) {
            case 'log':
                /** @var RequestFormInterface $requestForm */
                $requestForm = GeneralUtility::makeInstance(
                    LogRequestForm::class,
                    $view,
                    $infoModuleController,
                    $extensionSettings,
                    $backendModuleMenu
                );
                break;
            case 'multiprocess':
                $processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
                $processService = GeneralUtility::makeInstance(
                    ProcessService::class,
                    $processRepository,
                    new SystemSleeper()
                );
                $requestForm = GeneralUtility::makeInstance(
                    MultiProcessRequestForm::class,
                    $view,
                    $infoModuleController,
                    $extensionSettings,
                    $backendModuleMenu,
                    $processService
                );
                break;
            case 'start':
            default:
                $requestForm = GeneralUtility::makeInstance(
                    StartRequestForm::class,
                    $view,
                    $infoModuleController,
                    $extensionSettings,
                    $backendModuleMenu
                );
        }

        return $requestForm;
    }
}
