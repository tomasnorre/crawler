<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend\Helper;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal since 12.0.10
 */
final class RequestHelper
{
    public static function getIntFromRequest(ServerRequestInterface $request, string $key, int $default = 0): int
    {
        $value = self::getValue($request, $key);

        return is_scalar($value) ? (int) $value : $default;
    }

    public static function getBoolFromRequest(ServerRequestInterface $request, string $key): bool
    {
        return !empty(self::getValue($request, $key));
    }

    public static function getStringFromRequest(
        ServerRequestInterface $request,
        string $key,
        string $default = ''
    ): string {
        $value = self::getValue($request, $key);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function getArrayFromRequest(ServerRequestInterface $request, string $key): array
    {
        $value = self::getValue($request, $key);
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Returns the value from the parsed body, or from the query parameters if the parsed body doesn't have it
     */
    private static function getValue(ServerRequestInterface $request, string $key): mixed
    {
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body[$key])) {
            return $body[$key];
        }

        return $request->getQueryParams()[$key] ?? null;
    }
}
