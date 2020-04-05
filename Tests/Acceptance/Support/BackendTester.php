<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\Support;

/**
 * This file is copied from
 * TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester
 *
 * The file my at any point later than 2020-03-28
 * differ from the copied content
 *
 * Tomas Norre Mikkelsen
 */

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

use _generated\BackendTesterActions;
use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Default backend admin or editor actor in the backend
 */
class BackendTester extends \Codeception\Actor
{
    use BackendTesterActions;
    use FrameSteps;

    /**
     * Define custom actions here
     */
}
