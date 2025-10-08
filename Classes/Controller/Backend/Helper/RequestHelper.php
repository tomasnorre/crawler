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
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $value = (is_array($body) ? ($body[$key] ?? null) : null)
            ?? ($query[$key] ?? null)
            ?? $default;

        return (int) $value;
    }

    public static function getBoolFromRequest(ServerRequestInterface $request, string $key): bool
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $value = (is_array($body) ? ($body[$key] ?? null) : null)
            ?? ($query[$key] ?? null);

        return !empty($value);
    }

    public static function getStringFromRequest(
        ServerRequestInterface $request,
        string $key,
        string $default = ''
    ): string {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $value = (is_array($body) ? ($body[$key] ?? null) : null)
            ?? ($query[$key] ?? null)
            ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function getArrayFromRequest(ServerRequestInterface $request, string $key): array
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $source = is_array($body) ? $body : $query;
        $value = $source[$key] ?? $query[$key] ?? null;

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }

}
