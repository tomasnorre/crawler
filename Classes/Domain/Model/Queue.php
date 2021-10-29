<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @internal since v9.2.5
 */
class Queue extends AbstractEntity
{
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
