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

use AOE\Crawler\Value\CrawlAction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class RequestFormFactory
{
    public function __construct()
    {
        // Perhaps this can be removed, will need to check.
    }

    public static function create(CrawlAction $selectedAction, StandaloneView $view): RequestForm
    {
        switch ($selectedAction->__toString()) {
            case 'log':
                /** @var RequestForm $requestForm */
                $requestForm = GeneralUtility::makeInstance(LogRequestForm::class, $view);
                break;
            case 'multiprocess':
                $requestForm = GeneralUtility::makeInstance(MultiProcessRequestForm::class, $view);
                break;
            case 'start':
            default:
                $requestForm = GeneralUtility::makeInstance(StartRequestForm::class, $view);
        }

        return $requestForm;
    }
}
