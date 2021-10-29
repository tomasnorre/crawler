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

namespace AOE\Crawler\Writer\FileWriter\CsvWriter;

use TYPO3\CMS\Core\Utility\CsvUtility;

/**
 * @internal since v9.2.5
 */
final class CrawlerCsvWriter implements CsvWriterInterface
{
    public const CARRIAGE_RETURN = 13;

    public function arrayToCsv(array $records): string
    {
        $csvLines = [];
        reset($records);

        $csvLines[] = $this->getRowHeaders($records);
        foreach ($records as $row) {
            $csvLines[] = CsvUtility::csvValues($row);
        }

        return implode(chr(self::CARRIAGE_RETURN) . chr(10), $csvLines);
    }

    private function getRowHeaders(array $lines): string
    {
        $fieldNames = array_keys(current($lines));
        return CsvUtility::csvValues($fieldNames);
    }
}
