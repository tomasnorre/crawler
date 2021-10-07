<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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

class VetoHookTestHelper
{
    public function returnTrue(array $params): bool
    {
        return is_array($params);
    }

    public function returnString(array $params): string
    {
        $string = json_encode($params, JSON_THROW_ON_ERROR);
        return 'Veto because of ' . $string;
    }
}
