<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Utility;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Utility\TcaUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(TcaUtility::class)]
class TcaUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']);
        parent::tearDown();
    }

    #[Test]
    public function getProcessingInstructionsKeepsConfigurationWithoutProcessingInstructions(): void
    {
        $configuration = [
            'items' => [
                [
                    'label' => 'Existing',
                    'value' => 'existing',
                ],
            ],
        ];

        self::assertSame($configuration, (new TcaUtility())->getProcessingInstructions($configuration));

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = 'not an array';
        self::assertSame($configuration, (new TcaUtility())->getProcessingInstructions($configuration));
    }

    #[Test]
    public function getProcessingInstructionsAddsItemWithIconOfExtensionPerProcessingInstruction(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = [
            'indexed_search' => [
                'key' => 'tx_indexedsearch_reindex',
                'value' => 'Re-indexing',
            ],
            'without_icon' => [
                'key' => 'tx_without_icon',
                'value' => 'Without icon',
            ],
        ];
        $packageWithIcon = $this->createStub(PackageInterface::class);
        $packageWithIcon->method('getPackagePath')->willReturn('/var/www/indexed_search/');
        $packageWithIcon->method('getPackageIcon')->willReturn('Resources/Public/Icons/Extension.svg');
        $packageWithoutIcon = $this->createStub(PackageInterface::class);
        $packageWithoutIcon->method('getPackagePath')->willReturn('/var/www/without_icon/');
        $packageWithoutIcon->method('getPackageIcon')->willReturn(null);
        $packageManager = $this->createStub(PackageManager::class);
        $packageManager->method('getPackage')->willReturnMap([
            ['indexed_search', $packageWithIcon],
            ['without_icon', $packageWithoutIcon],
        ]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);

        self::assertSame([
            'items' => [
                [
                    'label' => 'Existing',
                    'value' => 'existing',
                ],
                [
                    'label' => 'Re-indexing [tx_indexedsearch_reindex]',
                    'value' => 'tx_indexedsearch_reindex',
                    'icon' => '/var/www/indexed_search/Resources/Public/Icons/Extension.svg',
                ],
                [
                    'label' => 'Without icon [tx_without_icon]',
                    'value' => 'tx_without_icon',
                    'icon' => '',
                ],
            ],
            'table' => 'tx_crawler_configuration',
            'field' => 'processing_instruction_filter',
        ], (new TcaUtility())->getProcessingInstructions([
            'items' => [
                [
                    'label' => 'Existing',
                    'value' => 'existing',
                ],
            ],
            'table' => 'tx_crawler_configuration',
            'field' => 'processing_instruction_filter',
        ]));
    }
}
