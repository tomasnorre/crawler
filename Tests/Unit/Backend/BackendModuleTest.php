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

        $this->subject = self::getAccessibleMock(BackendModule::class, ['getLanguageService'], [], '', false);
        $this->subject->expects($this->any())->method('getLanguageService')->willReturn($mockedLanguageService);

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
        self::assertArrayHasKey('crawlaction', $modMenu);
        self::assertArrayHasKey('log_resultLog', $modMenu);
        self::assertArrayHasKey('log_feVars', $modMenu);
        self::assertArrayHasKey('processListMode', $modMenu);
        self::assertArrayHasKey('log_display', $modMenu);
        self::assertArrayHasKey('itemsPerPage', $modMenu);
    }
}
