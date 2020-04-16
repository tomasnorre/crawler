<?php
declare(strict_types=1);

namespace AOE\Crawler\Tests\Functioanal\Utility;

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

use AOE\Crawler\Utility\PhpBinaryUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class PhpBinaryUtilityTest extends FunctionalTestCase
{

    /**
     * @test
     */
    public function getPhpBinaryThrowExpectionAsExtensionSettingsIsEmpty(): void
    {
        $this->expectExceptionCode(1587066853);
        $this->expectExceptionMessage('ExtensionSettings are empty');
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $this->assertEquals(
            PhpBinaryUtility::getPhpBinary(),
            'tomas'
        );
    }

    /**
     * @test
     */
    public function getPhpBinaryThrowsExceptionAsBinaryDoesNotExist(): void
    {
        $this->expectExceptionCode(1587068215);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => '',
            'phpBinary' => 'non-existing-binary'
        ];

        PhpBinaryUtility::getPhpBinary();
    }

    /**
     * @test
     * @dataProvider getPhpBinaryDataProvider
     */
    public function getPhpBinary(string $phpPath, string $phpBinary, string $expected): void
    {

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => $phpPath,
            'phpBinary' => $phpBinary
        ];

        $this->assertEquals(
            PhpBinaryUtility::getPhpBinary(),
            $expected
        );
    }

    public function getPhpBinaryDataProvider(): array
    {
        return [
            'php set to standard PHP' => [
                'phpPath' => '',
                'phpBinary' => 'php',
                'expected' => '/usr/bin/php'
            ],

            'phpPath is set' => [
                'phpPath' => '/complete/path/to/php',
                'phpBinary' => '/this/value/is/not/important/as/phpPath/is/set',
                'expected' => '/complete/path/to/php'
            ]
        ];
    }
}