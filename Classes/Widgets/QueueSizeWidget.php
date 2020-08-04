<?php

declare(strict_types=1);

namespace AOE\Crawler\Widgets;

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

use TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class QueueSizeWidget implements WidgetInterface
{
    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var NumberWithIconDataProviderInterface
     */
    private $dataProvider;

    /**
     * @var array
     */
    private $options;

    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        NumberWithIconDataProviderInterface $dataProvider,
        StandaloneView $view,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('QueueSizeWidget');
        $this->view->assignMultiple([
            'icon' => $this->options['icon'],
            'title' => $this->options['title'],
            'number' => $this->dataProvider->getNumber(),
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }
}
