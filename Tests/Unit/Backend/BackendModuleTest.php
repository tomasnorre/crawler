<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Backend;

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

use AOE\Crawler\Backend\BackendModule;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;

class BackendModuleTest extends UnitTestCase
{
    /**
     * @var BackendModule
     */
    protected $subject;

    protected function setUp(): void
    {
        $mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->expects($this->any())->method('sL')->willReturn('language string');

        $GLOBALS['LANG'] = $mockedLanguageService;
        $this->subject = self::getAccessibleMock(BackendModule::class, ['dummy'], [], '', false);

        $jsonCompatibilityConverter = new JsonCompatibilityConverter();
        $this->subject->_set('jsonCompatibilityConverter', $jsonCompatibilityConverter);
    }

    /**
     * @test
     */
    public function modMenuReturnsExpectedArray(): void
    {
        $modMenu = $this->subject->modMenu();

        self::assertIsArray($modMenu);
        self::assertCount(
            7,
            $modMenu
        );

        self::assertArrayHasKey('depth', $modMenu);
        self::assertArrayHasKey('0', $modMenu['depth']);
        self::assertArrayHasKey('1', $modMenu['depth']);
        self::assertArrayHasKey('2', $modMenu['depth']);
        self::assertArrayHasKey('3', $modMenu['depth']);
        self::assertArrayHasKey('4', $modMenu['depth']);
        self::assertArrayHasKey('99', $modMenu['depth']);
        self::assertIsString($modMenu['depth'][0]);
        self::assertIsString($modMenu['depth'][1]);
        self::assertIsString($modMenu['depth'][2]);
        self::assertIsString($modMenu['depth'][3]);
        self::assertIsString($modMenu['depth'][4]);
        self::assertIsString($modMenu['depth'][99]);
        self::assertArrayHasKey('crawlaction', $modMenu);
        self::assertArrayHasKey('start', $modMenu['crawlaction']);
        self::assertArrayHasKey('log', $modMenu['crawlaction']);
        self::assertArrayHasKey('multiprocess', $modMenu['crawlaction']);
        self::assertIsString($modMenu['crawlaction']['start']);
        self::assertIsString($modMenu['crawlaction']['log']);
        self::assertIsString($modMenu['crawlaction']['multiprocess']);
        self::assertArrayHasKey('log_resultLog', $modMenu);
        self::assertArrayHasKey('log_feVars', $modMenu);
        self::assertArrayHasKey('processListMode', $modMenu);
        self::assertArrayHasKey('log_display', $modMenu);
        self::assertArrayHasKey('all', $modMenu['log_display']);
        self::assertArrayHasKey('pending', $modMenu['log_display']);
        self::assertArrayHasKey('finished', $modMenu['log_display']);
        self::assertIsString($modMenu['log_display']['all']);
        self::assertIsString($modMenu['log_display']['pending']);
        self::assertIsString($modMenu['log_display']['finished']);
        self::assertArrayHasKey('itemsPerPage', $modMenu);
        self::assertArrayHasKey('5', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('10', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('50', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('0', $modMenu['itemsPerPage']);
        self::assertIsString($modMenu['itemsPerPage'][5]);
        self::assertIsString($modMenu['itemsPerPage'][10]);
        self::assertIsString($modMenu['itemsPerPage'][50]);
        self::assertIsString($modMenu['itemsPerPage'][0]);
    }
}
