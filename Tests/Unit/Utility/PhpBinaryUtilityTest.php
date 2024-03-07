<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Utility;

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
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Utility\PhpBinaryUtility::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Configuration\ExtensionConfigurationProvider::class)]
class PhpBinaryUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPhpBinaryThrowExpectionAsExtensionSettingsIsEmpty(): void
    {
        $this->expectExceptionCode(1_587_066_853);
        $this->expectExceptionMessage('ExtensionSettings are empty');
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        PhpBinaryUtility::getPhpBinary();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPhpBinaryThrowsExceptionAsBinaryDoesNotExist(): void
    {
        $this->expectExceptionCode(1_587_068_215);
        $this->expectExceptionMessage('The phpBinary: "non-existing-binary" could not be found!');
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => '',
            'phpBinary' => 'non-existing-binary',
        ];

        PhpBinaryUtility::getPhpBinary();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPhpBinaryDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getPhpBinary(string $phpPath, string $phpBinary, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => $phpPath,
            'phpBinary' => $phpBinary,
        ];

        $this->assertEquals(PhpBinaryUtility::getPhpBinary(), $expected);
    }

    public static function getPhpBinaryDataProvider(): iterable
    {
        yield 'php set to standard PHP' => [
            'phpPath' => '',
            'phpBinary' => 'php',
            // Done to accept that travis stores PHP binaries elsewhere as standard unix
            'expected' => CommandUtility::getCommand('php'),
        ];

        yield 'phpPath is set' => [
            'phpPath' => '/complete/path/to/php',
            'phpBinary' => '/this/value/is/not/important/as/phpPath/is/set',
            'expected' => '/complete/path/to/php',
        ];
    }
}
