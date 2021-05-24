<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Service\PageService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageServiceTest extends UnitTestCase
{
    /**
     * @var PageService
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = GeneralUtility::makeInstance(PageService::class);
    }

    /**
     * @test
     *
     * @dataProvider checkIfPageShouldBeSkippedDataProvider
     */
    public function checkIfPageShouldBeSkipped(array $extensionSetting, array $pageRow, array $excludeDoktype, array $pageVeto, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $extensionSetting;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] = $pageVeto;

        self::assertEquals(
            $expected,
            $this->subject->checkIfPageShouldBeSkipped($pageRow)
        );
    }

    /**
     * @return array
     */
    public function checkIfPageShouldBeSkippedDataProvider()
    {
        return [
            'Page of doktype 1 - Standard' => [
                'extensionSetting' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => false,
            ],
            'Extension Setting do not crawl hidden pages and page is hidden' => [
                'extensionSetting' => ['crawlHiddenPages' => false],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 1,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because page is hidden',
            ],
            'Page of doktype 3 - External Url' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 3,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page of doktype 4 - Shortcut' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 4,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page of doktype 155 - Custom' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 155,
                    'hidden' => 0,
                ],
                'excludeDoktype' => ['custom' => 155],
                'pageVeto' => [],
                'expected' => 'Doktype was excluded by "custom"',
            ],
            'Page of doktype 255 - Out of allowed range' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 255,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype is not allowed',
            ],
            'Page veto exists' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnTrue'],
                'expected' => 'Veto from hook "veto-func"',
            ],
            'Page veto exists - string' => [
                'extensionSettings' => [],
                'pageRow' => [
                    'doktype' => 1,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnString'],
                'expected' => 'Veto because of {"pageRow":{"doktype":1,"hidden":0}}',
            ],
        ];
    }
}
