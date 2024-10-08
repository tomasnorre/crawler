<?php

declare(strict_types=1);

/*
 * (c) 2024-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Functional\ContextMenu;

use AOE\Crawler\ContextMenu\ItemProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ItemProviderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private $oldBackendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->expects($this->any())->method('sL')->willReturn('language string');
        $GLOBALS['LANG'] = $mockedLanguageService;
        $this->oldBackendUser = $GLOBALS['BE_USER'] ?? null;
        $backendUserStub = $this->createStub(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserStub;
    }

    protected function tearDown(): void
    {
        if ($this->oldBackendUser) {
            $GLOBALS['BE_USER'] = $this->oldBackendUser;
        } else {
            unset($GLOBALS['BE_USER']);
        }
    }

    #[Test]
    public function addItemsContactsItems(): void
    {
        $newItems = [
            'other-ext' => [
                'type' => 'item',
                'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:contextMenu.label',
                'iconIdentifier' => 'tx-other-ext',
                'callbackAction' => 'other-ext-callback',
            ],
        ];

        $subject = new ItemProvider();
        self::assertArrayHasKey('crawler', $subject->addItems($newItems));
        self::assertArrayHasKey('other-ext', $subject->addItems($newItems));
    }
}
