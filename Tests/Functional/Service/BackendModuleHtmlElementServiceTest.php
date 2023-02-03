<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

/*
 * (c) 2023-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Service\BackendModuleHtmlElementService;
use AOE\Crawler\Tests\Functional\LanguageServiceTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleHtmlElementServiceTest extends FunctionalTestCase
{
    use LanguageServiceTestTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private BackendModuleHtmlElementService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupLanguageService();
        $this->subject = GeneralUtility::makeInstance(BackendModuleHtmlElementService::class);
    }

    /**
     * @test
     */
    public function getItemsPerPageDropDownHtmlWithEmptyMenuItemsReturnStringWithLabel(): void
    {
        $html = $this->subject->getItemsPerPageDropDownHtml(1, 10, [], []);
        self::assertEquals('Items per page: ', $html);
    }

    /**
     * @test
     */
    public function getItemsPerPageDropDownHtmlReturnHtml(): void
    {
        $html = $this->subject->getItemsPerPageDropDownHtml(
            1,
            10, // The Selected value
            $this->getItemsPerPageArray(),
            $this->getQueryParameters()
        );

        self::assertStringContainsString('Items per page: ', $html);
        self::assertStringContainsString('select name="itemsPerPage"', $html);
        self::assertStringContainsString('data-menu-identifier="items-per-page"', $html);
        self::assertStringContainsString('value="5"', $html);
        self::assertStringContainsString('value="10" selected="selected"', $html);
        self::assertStringContainsString('value="50"', $html);
        self::assertStringContainsString('value="0"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;ShowFeVars=1&amp;ShowResultLog=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;itemsPerPage=${value}', $html);
    }

    /**
     * @test
     */
    public function getShowFeVarsCheckBoxHtmlReturnsHtml(): void
    {
        // without quiPath
        $html = $this->subject->getShowFeVarsCheckBoxHtml(1, '1', '', $this->getQueryParameters());

        self::assertStringContainsString('Show FE Vars', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowFeVars" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowResultLog=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowFeVars=${value}"', $html);
        self::assertStringNotContainsString('qid_details', $html);

        // with quiPath
        $html = $this->subject->getShowFeVarsCheckBoxHtml(1, '1', '&qid_details=1', $this->getQueryParameters());

        self::assertStringContainsString('Show FE Vars', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowFeVars" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;qid_details=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowResultLog=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowFeVars=${value}"', $html);
    }

    /**
     * @test
     */
    public function getShowResultLogCheckBoxHtmlReturnsHtml(): void
    {
        // without quiPath
        $html = $this->subject->getShowResultLogCheckBoxHtml(1, '1', '', $this->getQueryParameters());

        self::assertStringContainsString('Show Result Log', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowResultLog" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowFeVars=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowResultLog=${value}"', $html);
        self::assertStringNotContainsString('qid_details', $html);

        // with quiPath
        $html = $this->subject->getShowResultLogCheckBoxHtml(1, '1', '&qid_details=1', $this->getQueryParameters());

        self::assertStringContainsString('Show Result Log', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowResultLog" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;qid_details=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowFeVars=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowResultLog=${value}"', $html);
    }

    private function getItemsPerPageArray(): array
    {
        return [
            'itemsPerPage' => [
                5 => 'limit to 5',
                10 => 'limit to 10',
                50 => 'limit to 50',
                0 => 'no limit',
            ],
        ];
    }

    private function getQueryParameters(): array
    {
        return [
            'setID' => 1234,
            'logDisplay' => 1,
            'itemsPerPage' => 10,
            'ShowFeVars' => 1,
            'ShowResultLog' => 1,
            'logDepth' => 4,
        ];
    }
}
