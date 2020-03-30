<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\Support\Step\Acceptance;

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

class Admin extends \AcceptanceTester
{
    public function loginAsAdmin(): void
    {
        $I = $this;
        $I->amOnPage('/');
        $I->canSee('Login');
        $I->fillField('#t3-username', 'admin');
        $I->fillField('#t3-password', 'password');
        $I->click('#t3-login-submit-section > button');
        $I->seeCookie('be_typo_user');
    }
}
