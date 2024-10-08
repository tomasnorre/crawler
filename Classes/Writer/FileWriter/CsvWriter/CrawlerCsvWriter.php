<?php

declare(strict_types=1);

namespace AOE\Crawler\Writer\FileWriter\CsvWriter;

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

use TYPO3\CMS\Core\Utility\CsvUtility;

/**
 * @internal since v9.2.5
 */
final class CrawlerCsvWriter implements CsvWriterInterface
{
    private const CARRIAGE_RETURN = 13;
    private const LINE_FEED = 10;

    public function arrayToCsv(array $records): string
    {
        $csvLines = [];
        reset($records);

        $csvLines[] = $this->getRowHeaders($records);
        foreach ($records as $row) {
            $csvLines[] = CsvUtility::csvValues($row);
        }

        return implode(chr(self::CARRIAGE_RETURN) . chr(self::LINE_FEED), $csvLines);
    }

    private function getRowHeaders(array $lines): string
    {
        $fieldNames = array_keys(current($lines));
        return CsvUtility::csvValues($fieldNames);
    }
}
