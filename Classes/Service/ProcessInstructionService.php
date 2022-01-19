<?php
declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v11.0.3
 */
class ProcessInstructionService
{
    public function isAllowed(string $processInstruction, array $incoming): bool
    {
        if (empty($incoming)) {
            return true;
        }

        foreach ($incoming as $pi) {
            if (GeneralUtility::inList($processInstruction, $pi)) {
                return true;
            }
        }
        return false;
    }
}
