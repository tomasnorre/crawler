<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend;

/*
 * (c) 2022-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use TYPO3\CMS\Core\SingletonInterface;

class BackendModuleSettings implements SingletonInterface
{
    protected string $processListMode = '';
    protected ?CrawlAction $crawlAction = null;
    protected int $depth = 0;
    protected string $logDisplay = 'all';
    protected int $itemPerPage = 10;
    protected bool $logResultLog = false;
    protected bool $logFeVars = false;

    public function getProcessListMode(): string
    {
        return $this->processListMode;
    }

    public function setProcessListMode(string $processListMode): void
    {
        $this->processListMode = $processListMode;
    }

    public function getCrawlAction(): ?CrawlAction
    {
        return $this->crawlAction;
    }

    public function setCrawlAction(CrawlAction $crawlAction): void
    {
        $this->crawlAction = $crawlAction;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function getLogDisplay(): string
    {
        return $this->logDisplay;
    }

    public function setLogDisplay(string $logDisplay): void
    {
        $this->logDisplay = $logDisplay;
    }

    public function getItemPerPage(): int
    {
        return $this->itemPerPage;
    }

    public function setItemPerPage(int $itemPerPage): void
    {
        $this->itemPerPage = $itemPerPage;
    }

    public function isLogResultLog(): bool
    {
        return $this->logResultLog;
    }

    public function setLogResultLog(bool $logResultLog): void
    {
        $this->logResultLog = $logResultLog;
    }

    public function isLogFeVars(): bool
    {
        return $this->logFeVars;
    }

    public function setLogFeVars(bool $logFeVars): void
    {
        $this->logFeVars = $logFeVars;
    }






}
