<?php

declare(strict_types=1);

namespace AOE\Crawler;

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

use AOE\Crawler\Widgets\Provider\QueueSizeDataProvider;
use AOE\Crawler\Widgets\QueueSizeWidget;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    if (ExtensionManagementUtility::isLoaded('dashboard')) {
        $services->set('dashboard.widget.crawler.queuesize')
            ->class(QueueSizeWidget::class)
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->arg('$dataProvider', new Reference(QueueSizeDataProvider::class))
            ->arg('$options', [
                'title' => 'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:dashboard.widget.queuesize.widget.title',
                'icon' => 'apps-toolbar-menu-search',
            ])
            ->tag('dashboard.widget', [
                'identifier' => 'crawler.queuesize',
                'groupNames' => 'crawler',
                'title' => 'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:dashboard.widget.queuesize.title',
                'description' => 'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:dashboard.widget.queuesize.description',
                'iconIdentifier' => 'content-widget-number',
                'height' => 'small',
                'width' => 'small',
            ])
        ;
    }
};
