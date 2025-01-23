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

use AOE\Crawler\Event\ModifySkipPageEvent;
use AOE\Crawler\Service\PageService;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Configuration\ExtensionConfigurationProvider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Event\ModifySkipPageEvent::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\PageService::class)]
class PageServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    protected \AOE\Crawler\Service\PageService $subject;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $modifySkipPageEvent = new ModifySkipPageEvent([]);
        $modifySkipPageEvent->setSkipped(false);

        $mockedEventDispatcher = $this->createStub(EventDispatcher::class);
        $mockedEventDispatcher->method('dispatch')->willReturn($modifySkipPageEvent);

        $this->subject = GeneralUtility::makeInstance(PageService::class, $mockedEventDispatcher);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('checkIfPageShouldBeSkippedDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function checkIfPageShouldBeSkipped(
        array $extensionSetting,
        array $pageRow,
        array $excludeDoktype,
        array $pageVeto,
        string $expected
    ): void {
        if (empty($expected)) {
            $expected = false;
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $extensionSetting;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] = $pageVeto;

        self::assertEquals($expected, $this->subject->checkIfPageShouldBeSkipped($pageRow));
    }

    public static function checkIfPageShouldBeSkippedDataProvider(): iterable
    {
        yield 'Page of doktype 1 - Standard' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => '',
        ];

        yield 'Extension Setting do not crawl hidden pages and page is hidden' => [
            'extensionSetting' => [
                'crawlHiddenPages' => false,
            ],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 1,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => 'Because page is hidden',
        ];

        yield 'Extension Setting empty and hidden is not set for page' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => '',
        ];

        yield 'Page of doktype 3 - External Url' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 3,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => 'Because doktype "3" is not allowed',
        ];

        yield 'Page of doktype 4 - Shortcut' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 4,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => 'Because doktype "4" is not allowed',
        ];

        yield 'Page of doktype 155 - Custom' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 155,
                'hidden' => 0,
            ],
            'excludeDoktype' => [
                'custom' => 155,
            ],
            'pageVeto' => [],
            'expected' => 'Doktype "155" was excluded by excludeDoktype configuration key "custom"',
        ];

        yield 'Page of doktype 199 - Spacer' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 199,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => 'Because doktype "199" is not allowed',
        ];

        yield 'Page of doktype 254 - Folder' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 254,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => [],
            'expected' => 'Because doktype "254" is not allowed',
        ];

        $typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() === 12) {
            yield 'Page of doktype 255 - Recycler' => [
                'extensionSetting' => [],
                'pageRow' => [
                    'doktype' => 255,
                    'hidden' => 0,
                ],
                'excludeDoktype' => [],
                'pageVeto' => [],
                'expected' => 'Because doktype "255" is not allowed',
            ];
        }

        /*
         * Left out as we want people to use the PSR-14 ModifySkipPageEvent instead,
         * kept for easy testing if needed.
        yield 'Page veto exists' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnTrue'],
            'expected' => 'Veto from hook "veto-func"',
        ];

        yield 'Page veto exists - string' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'pageVeto' => ['veto-func' => VetoHookTestHelper::class . '->returnString'],
            'expected' => 'Veto because of {"pageRow":{"doktype":1,"hidden":0}}',
        ];
        */
    }
}
