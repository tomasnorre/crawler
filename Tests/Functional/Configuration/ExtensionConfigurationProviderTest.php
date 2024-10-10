<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Configuration;

/*
 * (c) 2024-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionConfigurationProviderTest extends FunctionalTestCase
{
    public function testExceptionIsThrownAndCatchedIfExtensionNotLoaded(): void
    {
        $subject = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);

        self::assertIsArray($subject->getExtensionConfiguration());
        self::assertEmpty($subject->getExtensionConfiguration());
    }
}
