<?php

declare(strict_types=1);

namespace AOE\Crawler\Converter;

/*
 * (c) 2020     AOE GmbH <dev@aoe.com>
 * (c) 2023-    Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Exception;

/**
 * @internal since v9.2.5
 */
class JsonCompatibilityConverter
{
    /**
     * This is implemented as we want to switch away from serialized data to json data, when the crawler is storing
     * in the database. To ensure that older crawler entries, which have already been stored as serialized data
     * still works, we have added this converter that can be used for the reading part. The writing part will be done
     * in json from now on.
     * @see https://github.com/tomasnorre/crawler/issues/417
     *
     * @throws Exception
     */
    public function convert(string $dataString): array|bool
    {
        if (empty($dataString)) {
            return false;
        }

        $decoded = '';
        try {
            $decoded = json_decode($dataString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Do nothing as we want to continue with unserialize as a test.
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        try {
            $deserialized = unserialize($dataString, [
                'allowed_classes' => false,
            ]);
        } catch (\Throwable) {
            return false;
        }

        if (is_object($deserialized)) {
            throw new \RuntimeException('Objects are not allowed: ' . var_export($deserialized, true), 1_593_758_307);
        }

        if (is_array($deserialized)) {
            return $deserialized;
        }

        return false;
    }
}
