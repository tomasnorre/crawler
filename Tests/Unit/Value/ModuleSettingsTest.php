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

use AOE\Crawler\Value\ModuleSettings;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ModuleSettingsTest extends UnitTestCase
{
    public const PROCESS_LIST_MODE = 'simple';

    public const CRAWL_ACTION = 'start';

    public const DEPTH = 5;

    public const LOG_RESULT_LOG = true;

    public const ITEMS_PER_PAGE = 10;

    public const LOG_DISPLAY = 'all';

    public const LOG_FE_VARS = true;

    /**
     * @test
     */
    public function fromArrayReturnExpectedObject(): void
    {
        $settingsArray = [
            'processListMode' => self::PROCESS_LIST_MODE,
            'crawlaction' => self::CRAWL_ACTION,
            'depth' => self::DEPTH,
            'log_resultLog' => self::LOG_RESULT_LOG,
            'itemPerPage' => self::ITEMS_PER_PAGE,
            'log_display' => self::LOG_DISPLAY,
            'log_feVars' => self::LOG_FE_VARS,
        ];

        $moduleSettings = ModuleSettings::fromArray($settingsArray);

        self::assertEquals(
            self::PROCESS_LIST_MODE,
            $moduleSettings->getProcessListMode()
        );

        self::assertEquals(
            self::CRAWL_ACTION,
            $moduleSettings->getCrawlAction()
        );

        self::assertEquals(
            self::DEPTH,
            $moduleSettings->getDepth()
        );

        self::assertEquals(
            self::LOG_RESULT_LOG,
            $moduleSettings->isLogResultLog()
        );

        self::assertEquals(
            self::ITEMS_PER_PAGE,
            $moduleSettings->getItemsPerPage()
        );

        self::assertEquals(
            self::LOG_DISPLAY,
            $moduleSettings->getLogDisplay()
        );

        self::assertEquals(
            self::LOG_FE_VARS,
            $moduleSettings->isLogFeVars()
        );
    }

    /**
     * @test
     */
    public function fromArrayWithUnexpectedArrayAsInput(): void
    {
        $settingsArray = [
            'processListMode' => self::PROCESS_LIST_MODE,
        ];

        $moduleSettings = ModuleSettings::fromArray($settingsArray);
    }
}
