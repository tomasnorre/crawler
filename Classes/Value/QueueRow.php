<?php

declare(strict_types=1);

namespace AOE\Crawler\Value;

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

/**
 * @internal
 */
class QueueRow
{
    /** @var string */
    public $pageTitle = '';

    /** @var string */
    public $pageTitleHTML = '';

    /** @var string */
    public $message;

    /** @var string */
    public $configurationKey;

    /** @var string */
    public $parameterConfig;

    /** @var string */
    public $valuesExpanded;

    /** @var string */
    public $urls;

    /** @var array */
    public $options;

    /** @var string */
    public $parameters;

    public function __construct(string $title = '')
    {
        $this->pageTitle = $title;
    }

    public function setPageTitleHTML(string $pageTitleHTML): void
    {
        $this->pageTitleHTML = $pageTitleHTML;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setConfigurationKey(string $configurationKey): void
    {
        $this->configurationKey = $configurationKey;
    }

    public function setParameterConfig(string $parameterConfig): void
    {
        $this->parameterConfig = $parameterConfig;
    }

    public function setValuesExpanded(string $valuesExpanded): void
    {
        $this->valuesExpanded = $valuesExpanded;
    }

    public function setUrls(string $urls): void
    {
        $this->urls = $urls;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setParameters(string $parameters): void
    {
        $this->parameters = $parameters;
    }
}
