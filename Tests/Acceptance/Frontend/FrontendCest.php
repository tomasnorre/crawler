<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\BackendModule;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Tests\Acceptance\Support\Step\Acceptance\FrontendUser;

class FrontendCest
{
    // Implemented in Playwright for TYPO3 V13
    public function canSeeHomePage(FrontendUser $I): void
    {
        $I->amOnPage('/');
        $I->waitForText('Search', 1);
        $I->waitForText('Login', 1);
    }

    // Implemented in Playwright for TYPO3 V13
    public function canSeeNewsPage(FrontendUser $I): void
    {
        $I->amOnPage('/news');
        $I->waitForText('No news available.');
    }

    // Implemented in Playwright for TYPO3 V13
    public function canSeeSearchPageAndSearchForTomasnorre(FrontendUser $I): void
    {
        $I->amOnPage('/search');
        $I->waitForText('Search', 1);
        $I->fillField('#tx-indexedsearch-searchbox-sword', 'tomasnorre');
        $I->click('#tx-indexedsearch-searchbox-button-submit');
        $I->waitForText('Displaying results 1 to 1');
    }
}
