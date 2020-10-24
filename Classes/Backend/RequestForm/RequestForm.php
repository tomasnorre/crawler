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

use AOE\Crawler\Value\ModuleSettings;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

interface RequestForm
{
    public function __construct(StandaloneView $view, InfoModuleController $infoModuleController);

    public function render($id, string $elementName, array $menuItems): string;
}
