<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @internal since v9.2.5
 */
class Configuration extends AbstractEntity
{
    protected string $name = '';
    protected int $forceSsl = 1;
    protected string $processingInstructionFilter = '';
    protected string $processingInstructionParameters = '';
    protected string $configuration = '';
    protected string $baseUrl = '';
    protected string $pidsonly = '';
    protected string $begroups = '';
    protected string $fegroups = '';
    protected string $exclude = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function isForceSsl()
    {
        return $this->forceSsl;
    }

    /**
     * @param int $forceSsl
     */
    public function setForceSsl($forceSsl): void
    {
        $this->forceSsl = $forceSsl;
    }

    /**
     * @return string
     */
    public function getProcessingInstructionFilter()
    {
        return $this->processingInstructionFilter;
    }

    /**
     * @param string $processingInstructionFilter
     */
    public function setProcessingInstructionFilter($processingInstructionFilter): void
    {
        $this->processingInstructionFilter = $processingInstructionFilter;
    }

    /**
     * @return string
     */
    public function getProcessingInstructionParameters()
    {
        return $this->processingInstructionParameters;
    }

    /**
     * @param string $processingInstructionParameters
     */
    public function setProcessingInstructionParameters($processingInstructionParameters): void
    {
        $this->processingInstructionParameters = $processingInstructionParameters;
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration($configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getPidsOnly(): string
    {
        return $this->pidsonly;
    }

    public function setPidsOnly(string $pidsOnly): void
    {
        $this->pidsonly = $pidsOnly;
    }

    /**
     * @return string
     */
    public function getBeGroups()
    {
        return $this->begroups;
    }

    /**
     * @param string $beGroups
     */
    public function setBeGroups($beGroups): void
    {
        $this->begroups = $beGroups;
    }

    /**
     * @return string
     */
    public function getFeGroups()
    {
        return $this->fegroups;
    }

    /**
     * @param string $feGroups
     */
    public function setFeGroups($feGroups): void
    {
        $this->fegroups = $feGroups;
    }

    /**
     * @return string
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * @param string $exclude
     */
    public function setExclude($exclude): void
    {
        $this->exclude = $exclude;
    }
}
