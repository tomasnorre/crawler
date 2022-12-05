<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * @internal since v12.0.0
 */
class BackendModuleSettings implements SingletonInterface
{
    public int $depth = 0;
    public string $logDisplay = 'all';
    public int $itemsPerPage = 10;
    public bool $showResultLog = true;
    public bool $showFeLog = false;

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

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    public function isShowResultLog(): bool
    {
        return $this->showResultLog;
    }

    public function setShowResultLog(bool $showResultLog): void
    {
        $this->showResultLog = $showResultLog;
    }

    public function isShowFeLog(): bool
    {
        return $this->showFeLog;
    }

    public function setShowFeLog(bool $showFeLog): void
    {
        $this->showFeLog = $showFeLog;
    }



}
