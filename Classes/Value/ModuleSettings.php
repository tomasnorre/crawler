<?php

declare(strict_types=1);

namespace AOE\Crawler\Value;

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

final class ModuleSettings
{
    /**
     * @var string
     */
    private $processListMode;

    /**
     * @var string
     */
    private $crawlAction;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var bool
     */
    private $logResultLog;

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var string
     */
    private $logDisplay;

    /**
     * @var bool
     */
    private $logFeVars;

    public function __construct(
        string $processListMode,
        string $crawlAction,
        int $depth,
        bool $logResultLog,
        int $itemsPerPage,
        string $logDisplay,
        bool $logFeVars
    ) {
        $this->processListMode = $processListMode;
        $this->crawlAction = $crawlAction;
        $this->depth = $depth;
        $this->logResultLog = $logResultLog;
        $this->itemsPerPage = $itemsPerPage;
        $this->logDisplay = $logDisplay;
        $this->logFeVars = $logFeVars;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['processListMode'],
            $array['crawlaction'],
            $array['depth'],
            $array['log_resultLog'],
            $array['itemPerPage'],
            $array['log_display'],
            $array['log_feVars']
        );
    }

    public function getProcessListMode(): string
    {
        return $this->processListMode;
    }

    public function getCrawlAction(): string
    {
        return $this->crawlAction;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function isLogResultLog(): bool
    {
        return $this->logResultLog;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getLogDisplay(): string
    {
        return $this->logDisplay;
    }

    public function isLogFeVars(): bool
    {
        return $this->logFeVars;
    }
}
