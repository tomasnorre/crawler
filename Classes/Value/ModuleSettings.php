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
    private $crawlAction;

    /**
     * @var int
     */
    private $pages;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var string
     */
    private $logDisplay;

    public function __construct(
        string $crawlAction = 'start',
        int $pages = 0,
        int $depth = 0,
        int $itemsPerPage = 5,
        string $logDisplay = 'all'
    ) {
        $this->crawlAction = $crawlAction;
        $this->pages = $pages;
        $this->depth = $depth;
        $this->itemsPerPage = $itemsPerPage;
        $this->logDisplay = $logDisplay;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            (string) $array['crawlaction'],
            (int) $array['pages'],
            (int) $array['depth'],
            (int) $array['itemPerPage'],
            (string) $array['log_display']
        );
    }

    /**
     * @return string
     */
    public function getCrawlAction(): string
    {
        return $this->crawlAction;
    }

    /**
     * @return int
     */
    public function getPages(): int
    {
        return $this->pages;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return int
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @return string
     */
    public function getLogDisplay(): string
    {
        return $this->logDisplay;
    }
}
