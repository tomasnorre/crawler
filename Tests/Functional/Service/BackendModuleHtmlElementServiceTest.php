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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleHtmlElementServiceTest extends FunctionalTestCase
{
    private BackendModuleHtmlElementService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(BackendModuleHtmlElementService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForItemsPerPageWithEmptyMenuItems(): void
    {
        $html = $this->subject->getFormElementSelect('itemsPerPage', 1, '10', [], []);
        self::assertEquals('', $html);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForItemsPerPageReturnSelect(): void
    {
        $html = $this->subject->getFormElementSelect(
            'itemsPerPage',
            1,
            '10', // The Selected value
            $this->getMenuItems(),
            $this->getQueryParameters()
        );

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForShowFeVarsReturnsCheckbox(): void
    {
        // without quiPath
        $html = $this->subject->getFormElementCheckbox('ShowFeVars', 1, '1', $this->getQueryParameters());

        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowFeVars" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowResultLog=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowFeVars=${value}"', $html);
        self::assertStringNotContainsString('qid_details', $html);

        // with quiPath
        $html = $this->subject->getFormElementCheckbox(
            'ShowFeVars',
            1,
            '1',
            $this->getQueryParameters(),
            '&qid_details=1',
        );

        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowFeVars" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;qid_details=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowResultLog=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowFeVars=${value}"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForShowResultLogReturnsCheckbox(): void
    {
        // without quiPath
        $html = $this->subject->getFormElementCheckbox('ShowResultLog', 1, '1', $this->getQueryParameters());

        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowResultLog" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowFeVars=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowResultLog=${value}"', $html);
        self::assertStringNotContainsString('qid_details', $html);

        // with quiPath
        $html = $this->subject->getFormElementCheckbox(
            'ShowResultLog',
            1,
            '1',
            $this->getQueryParameters(),
            '&qid_details=1'
        );

        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('name="ShowResultLog" value="1"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;qid_details=1&amp;setID=1234', $html);
        self::assertStringContainsString('&amp;logDisplay=1&amp;itemsPerPage=10&amp;ShowFeVars=1', $html);
        self::assertStringContainsString('&amp;logDepth=4&amp;ShowResultLog=${value}"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForLogDisplayReturnsSelect(): void
    {
        $html = $this->subject->getFormElementSelect(
            'logDisplay',
            1,
            'finished',
            $this->getMenuItems(),
            $this->getQueryParameters()
        );

        self::assertStringContainsString('<select name="logDisplay"', $html);
        self::assertStringContainsString(
            'data-navigate-value="index.php?id=1&amp;setID=1234&amp;itemsPerPage=10',
            $html
        );
        self::assertStringContainsString('&amp;ShowFeVars=1&amp;ShowResultLog=1&amp;logDepth=4', $html);
        self::assertStringContainsString('&amp;logDisplay=${value}"', $html);

        self::assertStringContainsString('<option value="all">', $html);
        self::assertStringContainsString('<option value="pending">', $html);
        self::assertStringContainsString('<option value="finished" selected="selected">', $html);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFormElementForLogDepthReturnsSelect(): void
    {
        $html = $this->subject->getFormElementSelect(
            'logDepth',
            1,
            '2',
            $this->getMenuItems(),
            $this->getQueryParameters()
        );

        self::assertStringContainsString('name="logDepth"', $html);
        self::assertStringContainsString('data-navigate-value="index.php?id=1&amp;setID=1234&amp;logDisplay=1', $html);
        self::assertStringContainsString('&amp;itemsPerPage=10&amp;ShowFeVars=1', $html);
        self::assertStringContainsString('&amp;ShowResultLog=1&amp;logDepth=${value}"', $html);

        self::assertStringContainsString('<option value="0"', $html);
        self::assertStringContainsString('<option value="1"', $html);
        self::assertStringContainsString('<option value="2" selected="selected">', $html);
        self::assertStringContainsString('<option value="3"', $html);
        self::assertStringContainsString('<option value="4"', $html);
        self::assertStringContainsString('<option value="99"', $html);
    }

    private function getMenuItems(): array
    {
        return [
            'itemsPerPage' => [
                5 => 'limit to 5',
                10 => 'limit to 10',
                50 => 'limit to 50',
                0 => 'no limit',
            ],
            'logDisplay' => [
                'all' => 'all',
                'pending' => 'pending',
                'finished' => 'finished',
            ],
            'logDepth' => [
                '0' => 'This page',
                '1' => '1 level',
                '2' => '2 levels',
                '3' => '3 levels',
                '4' => '4 levels',
                '99' => 'Infinite',
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
