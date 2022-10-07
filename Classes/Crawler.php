<?php

declare(strict_types=1);

namespace AOE\Crawler;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
final class Crawler implements SingletonInterface
{
    private string $processFilename;

    public function __construct(?string $processFilename = null)
    {
        $this->processFilename = $processFilename ?: Environment::getVarPath() . '/lock/tx_crawler.proc';
        $this->setDisabled(false);
    }

    public function setDisabled(bool $disabled = true): void
    {
        if ($disabled) {
            GeneralUtility::writeFile($this->processFilename, '');
        } elseif (is_file($this->processFilename)) {
            unlink($this->processFilename);
        }
    }

    public function isDisabled(): bool
    {
        return is_file($this->processFilename);
    }
}
