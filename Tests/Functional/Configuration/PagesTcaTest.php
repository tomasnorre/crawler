<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Configuration;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class PagesTcaTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    #[Test]
    public function exclusionFieldsAreShownInTheCrawlerPaletteAfterTheMiscellaneousPalette(): void
    {
        self::assertSame(
            'tx_crawler_exclude, tx_crawler_exclude_subpages',
            $GLOBALS['TCA']['pages']['palettes']['crawler']['showitem']
        );
        self::assertStringContainsString(
            '--palette--;;miscellaneous, --palette--;;crawler',
            $GLOBALS['TCA']['pages']['types'][(string) PageRepository::DOKTYPE_DEFAULT]['showitem']
        );
    }
}
