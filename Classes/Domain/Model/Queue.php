<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @internal since v9.2.5
 */
class Queue extends AbstractEntity
{
    protected int $qid = 0;
    protected int $pageId = 0;
    protected string $parameters = '';
    protected string $parametersHash = '';
    protected string $configurationHash = '';
    protected bool $scheduled = false;
    protected int $execTime = 0;
    protected int $setId = 0;
    protected string $resultData = '';
    protected bool $processScheduled = false;
    protected string $processId = '';
    protected string $processIdCompleted = '';
    protected string $configuration = '';

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
    public function setQid($qid): void
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
    public function setPageId($pageId): void
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
    public function setParameters($parameters): void
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
    public function setParametersHash($parametersHash): void
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
    public function setConfigurationHash($configurationHash): void
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
    public function setScheduled($scheduled): void
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
    public function setExecTime($execTime): void
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
    public function setSetId($setId): void
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
    public function setResultData($resultData): void
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
    public function setProcessScheduled($processScheduled): void
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
    public function setProcessId($processId): void
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
    public function setProcessIdCompleted($processIdCompleted): void
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
    public function setConfiguration($configuration): void
    {
        $this->configuration = $configuration;
    }
}
