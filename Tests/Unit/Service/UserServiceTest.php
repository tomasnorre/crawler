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

use AOE\Crawler\Service\UserService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \AOE\Crawler\Service\UserService
 */
class UserServiceTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider hasGroupAccessDataProvider
     */
    public function hasGroupAccess(string $groupList, string $accessList, bool $expected): void
    {
        self::assertEquals($expected, UserService::hasGroupAccess($groupList, $accessList));
    }

    public static function hasGroupAccessDataProvider(): iterable
    {
        yield 'Do not have access' => [
            'groupList' => '1,2,3',
            'accessList' => '4,5,6',
            'expected' => false,
        ];
        yield 'Do have access' => [
            'groupList' => '1,2,3,4',
            'accessList' => '4,5,6',
            'expected' => true,
        ];
        yield 'Access List empty' => [
            'groupList' => '1,2,3',
            'accessList' => '',
            'expected' => true,
        ];
    }
}
