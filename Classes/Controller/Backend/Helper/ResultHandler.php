<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend\Helper;

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

/**
 * @internal since v9.2.5
 */
class ResultHandler
{
    /**
     * Extract the log information from the current row and retrieve it as formatted string.
     */
    public static function getResultLog(array $resultRow): string
    {
        $content = '';
        if (array_key_exists('result_data', $resultRow)) {
            $requestContent = json_decode((string) $resultRow['result_data'], true) ?: [];
            if (is_bool($requestContent) || !array_key_exists('content', $requestContent)) {
                return $content;
            }
            $requestResult = json_decode((string) $requestContent['content'], true);

            if (is_array($requestResult) && array_key_exists('log', $requestResult)) {
                $content = implode(chr(10), $requestResult['log']);
            }
        }
        return $content;
    }

    public static function getResStatus(array|bool $requestContent): string
    {
        if (empty($requestContent)) {
            return '-';
        }
        if (is_bool($requestContent) || !array_key_exists('content', $requestContent)) {
            return 'Content index does not exists in requestContent array';
        }

        $requestResult = json_decode((string) $requestContent['content'], true);
        if (is_array($requestResult)) {
            if (empty($requestResult['errorlog'])) {
                return 'OK';
            }
            return implode("\n", $requestResult['errorlog']);
        }

        return 'Error - no info, sorry!';
    }

    /**
     * Find Fe vars
     */
    public static function getResFeVars(array $resultData): array
    {
        if (empty($resultData)) {
            return [];
        }
        $requestResult = json_decode((string) $resultData['content'], true);
        if (is_bool($requestResult)) {
            return [];
        }
        return $requestResult['vars'] ?? [];
    }
}
