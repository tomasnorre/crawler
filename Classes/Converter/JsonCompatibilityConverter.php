<?php

declare(strict_types=1);

namespace AOE\Crawler\Converter;

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
class JsonCompatibilityConverter
{
    /**
     * This is implemented as we want to switch away from serialized data to json data, when the crawler is storing
     * in the database. To ensure that older crawler entries, which have already been stored as serialized data
     * still works, we have added this converter that can be used for the reading part. The writing part will be done
     * in json from now on.
     * @see https://github.com/AOEpeople/crawler/issues/417
     *
     * @return array|bool
     * @throws \Exception
     */
    public function convert(string $dataString)
    {
        $unserialized = unserialize($dataString, ['allowed_classes' => false]);
        if (is_object($unserialized)) {
            throw new \Exception('Objects are not allowed: ' . var_export($unserialized, true), 1593758307);
        }

        if ($unserialized && ! is_object($unserialized)) {
            return $unserialized;
        }

        $decoded = json_decode($dataString, true);
        if ($decoded) {
            return $decoded;
        }

        return false;
    }
}
