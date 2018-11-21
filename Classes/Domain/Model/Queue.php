<?php
namespace AOE\Crawler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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
 * Class Queue
 */
class Queue extends AbstractEntity
{
    /**
     * @var array
     */
    protected $row;

    /**
     * @var int
     */
    protected $qid = 0;

    /**
     * @var int
     */
    protected $pageId = 0;

    /**
     * @var string
     */
    protected $parameters = '';

    /**
     * @var string
     */
    protected $parametersHash = '';

    /**
     * @var string
     */
    protected $configurationHash = '';

    /**
     * @var bool
     */
    protected $scheduled = false;

    /**
     * @var int
     */
    protected $execTime = 0;

    /**
     * @var int
     */
    protected $setId = 0;

    /**
     * @var string
     */
    protected $resultData = '';

    /**
     * @var bool
     */
    protected $processScheduled = false;

    /**
     * @var string
     */
    protected $processId = '';

    /**
     * @var string
     */
    protected $processIdCompleted = '';

    /**
     * @var string
     */
    protected $configuration = '';

    /**
     * @param array $ro
     */
    public function __construct($row = [])
    {
        $this->row = $row;
    }

    /**
     * Returns the properties of the object as array
     *
     * @return array
     * @deprecated since crawler v6.2.2, will be removed in crawler v7.0.0.
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return int
     */
    public function getQid()
    {
        return $this->qid;
    }

    /**
     * @param int $qid
     */
    public function setQid($qid)
    {
        $this->qid = $qid;
    }

    /**
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getParametersHash()
    {
        return $this->parametersHash;
    }

    /**
     * @param string $parametersHash
     */
    public function setParametersHash($parametersHash)
    {
        $this->parametersHash = $parametersHash;
    }

    /**
     * @return string
     */
    public function getConfigurationHash()
    {
        return $this->configurationHash;
    }

    /**
     * @param string $configurationHash
     */
    public function setConfigurationHash($configurationHash)
    {
        $this->configurationHash = $configurationHash;
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->scheduled;
    }

    /**
     * @param bool $scheduled
     */
    public function setScheduled($scheduled)
    {
        $this->scheduled = $scheduled;
    }

    /**
     * @return int
     */
    public function getExecTime()
    {
        return $this->execTime;
    }

    /**
     * @param int $execTime
     */
    public function setExecTime($execTime)
    {
        $this->execTime = $execTime;
    }

    /**
     * @return int
     */
    public function getSetId()
    {
        return $this->setId;
    }

    /**
     * @param int $setId
     */
    public function setSetId($setId)
    {
        $this->setId = $setId;
    }

    /**
     * @return string
     */
    public function getResultData()
    {
        return $this->resultData;
    }

    /**
     * @param string $resultData
     */
    public function setResultData($resultData)
    {
        $this->resultData = $resultData;
    }

    /**
     * @return bool
     */
    public function isProcessScheduled()
    {
        return $this->processScheduled;
    }

    /**
     * @param bool $processScheduled
     */
    public function setProcessScheduled($processScheduled)
    {
        $this->processScheduled = $processScheduled;
    }

    /**
     * @return string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param string $processId
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    /**
     * @return string
     */
    public function getProcessIdCompleted()
    {
        return $this->processIdCompleted;
    }

    /**
     * @param string $processIdCompleted
     */
    public function setProcessIdCompleted($processIdCompleted)
    {
        $this->processIdCompleted = $processIdCompleted;
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


}
