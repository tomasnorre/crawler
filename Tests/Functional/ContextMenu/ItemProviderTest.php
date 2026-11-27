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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ItemProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private $oldBackendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->method('sL')->willReturn('language string');
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

    #[Test]
    public function addItemsAddsAttributesToStartCrawlingWithTheConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');
        $subject = new ItemProvider();
        $subject->setContext('tx_crawler_configuration', '1');

        self::assertSame(
            [
                'data-dispatch-action' => 'TYPO3.ModuleMenu.showModule',
                'data-dispatch-args-list' => 'web_site_crawler_start,&' . GeneralUtility::quoteJSvalue(
                    '&SET[function]=AOE\\Crawler\\Backend\\BackendModule&SET[crawlaction]=start'
                    . '&configurationSelection[]=Not hidden or deleted'
                ),
            ],
            $subject->addItems([])['crawler']['additionalAttributes']
        );
    }

    #[Test]
    public function addItemsAddsNoAttributesForMissingConfiguration(): void
    {
        $subject = new ItemProvider();
        $subject->setContext('tx_crawler_configuration', '4711');

        self::assertSame([], $subject->addItems([])['crawler']['additionalAttributes']);
    }
}
