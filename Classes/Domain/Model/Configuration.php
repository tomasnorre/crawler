<?php
namespace AOE\Crawler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Configuration
 */
class Configuration extends AbstractEntity
{

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var bool
     */
    protected $forceSsl = true;

    /**
     * @var string
     */
    protected $processingInstructionFilter = '';

    /**
     * @var string
     */
    protected $processingInstructionParameters = '';

    /**
     * @var string
     */
    protected $configuration = '';

    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var string
     */
    protected $sysDomainBaseUrl = '';

    /**
     * @var string
     */
    protected $pidsonly = '';

    /**
     * @var string
     */
    protected $begroups = '';

    /**
     * @var string
     */
    protected $fegroups = '';

    /**
     * @var int
     */
    protected $realurl = 0;

    /**
     * @var int
     */
    protected $chash = 0;

    /**
     * @var string
     */
    protected $exclude = '';

    /**
     * @var int
     */
    protected $rootTemplatePid = 0;

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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isForceSsl()
    {
        return $this->forceSsl;
    }

    /**
     * @param bool $forceSsl
     */
    public function setForceSsl($forceSsl)
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
    public function setProcessingInstructionFilter($processingInstructionFilter)
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
    public function setProcessingInstructionParameters($processingInstructionParameters)
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
    public function setConfiguration($configuration)
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
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return string
     */
    public function getSysDomainBaseUrl()
    {
        return $this->sysDomainBaseUrl;
    }

    /**
     * @param string $sysDomainBaseUrl
     */
    public function setSysDomainBaseUrl($sysDomainBaseUrl)
    {
        $this->sysDomainBaseUrl = $sysDomainBaseUrl;
    }

    /**
     * @return mixed
     */
    public function getPidsOnly()
    {
        return $this->pidsonly;
    }

    /**
     * @param mixed $pidsOnly
     */
    public function setPidsOnly($pidsOnly)
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
    public function setBeGroups($beGroups)
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
    public function setFeGroups($feGroups)
    {
        $this->fegroups = $feGroups;
    }

    /**
     * @return int
     */
    public function getRealUrl()
    {
        return $this->realurl;
    }

    /**
     * @param int $realUrl
     */
    public function setRealUrl($realUrl)
    {
        $this->realurl = $realUrl;
    }

    /**
     * @return int
     */
    public function getCHash()
    {
        return $this->chash;
    }

    /**
     * @param int $cHash
     */
    public function setCHash($cHash)
    {
        $this->chash = $cHash;
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
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * @return int
     */
    public function getRootTemplatePid()
    {
        return $this->rootTemplatePid;
    }

    /**
     * @param int $rootTemplatePid
     */
    public function setRootTemplatePid($rootTemplatePid)
    {
        $this->rootTemplatePid = $rootTemplatePid;
    }
}

