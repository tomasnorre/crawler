<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Value;

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

use AOE\Crawler\Value\ModuleMenu;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ModuleMenuTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fromArrayReturnSelf(): void
    {
        self::assertInstanceOf(
            ModuleMenu::class,
            ModuleMenu::fromArray($this->getModuleMenu())
        );
    }

    private function getModuleMenu(): array
    {
        return [
            'depth' => [
                0 => 'Value 0',
                1 => 'Value 0',
                2 => 'Value 0',
                3 => 'Value 0',
                4 => 'Value 0',
                99 => 'Value 0',
            ],
            'crawlaction' => [
                'start' => 'Start Crawling',
                'log' => 'Crawler log',
                'multiprocess' => 'Crawling Processes',
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'log_display' => [
                'all' => 'Value 0',
                'pending' => 'Value 0',
                'finished' => 'Value 0',
            ],
            'itemsPerPage' => [
                '5' => 'Value 0',
                '10' => 'Value 0',
                '50' => 'Value 0',
                '0' => 'Value 0',
            ],
        ];
    }
}
