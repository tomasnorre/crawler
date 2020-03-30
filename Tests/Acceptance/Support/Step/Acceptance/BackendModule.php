<?php

declare(strict_types=1);

namespace Step\Acceptance;

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

use AOE\Crawler\Tests\Acceptance\Support\Helper\PageTree;
use AOE\Crawler\Tests\Acceptance\Support\Step\Acceptance\Admin;

class BackendModule extends \AcceptanceTester
{
    public function openCrawlerBackendModule(Admin $I, PageTree $pageTree): void
    {
        $I->loginAsAdmin();
        $I->click('#web_info');
        // Due to slow response time.
        $I->wait(15);
        //$I->canSee('Page Information');
        $pageTree->openPath(['Congratulations']);
        // Due to slow response time.
        $I->wait(15);
    }
}
