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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Event\ModifySkipPageEvent;
use AOE\Crawler\Service\PageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExtensionConfigurationProvider::class)]
#[CoversClass(ModifySkipPageEvent::class)]
#[CoversClass(PageService::class)]
class PageServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    protected PageService $subject;

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

    #[DataProvider('checkIfPageShouldBeSkippedDataProvider')]
    #[Test]
    public function checkIfPageShouldBeSkipped(
        array $extensionSetting,
        array $pageRow,
        array $excludeDoktype,
        string $expected
    ): void {
        if (empty($expected)) {
            $expected = false;
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $extensionSetting;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] = $excludeDoktype;

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
            'expected' => 'Because page is hidden',
        ];

        yield 'Extension Setting empty and hidden is not set for page' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 1,
            ],
            'excludeDoktype' => [],
            'expected' => '',
        ];

        yield 'Page of doktype 3 - External Url' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 3,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "3" is not allowed',
        ];

        yield 'Page of doktype 4 - Shortcut' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 4,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
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
            'expected' => 'Doktype "155" was excluded by excludeDoktype configuration key "custom"',
        ];

        yield 'Page of doktype 199 - Spacer' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 199,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "199" is not allowed',
        ];

        yield 'Page of doktype 254 - Folder' => [
            'extensionSetting' => [],
            'pageRow' => [
                'doktype' => 254,
                'hidden' => 0,
            ],
            'excludeDoktype' => [],
            'expected' => 'Because doktype "254" is not allowed',
        ];
    }
}
