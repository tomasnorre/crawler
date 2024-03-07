<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Utility;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Utility\TcaUtility::class)]
class TcaUtilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    #[\PHPUnit\Framework\Attributes\DataProvider('getProcessingInstructionsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getProcessingInstructions($procInstructions, array $configuration, $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = $procInstructions;

        $subject = new TcaUtility();

        if (!empty($expected['items'][0]['icon'])) {
            self::assertStringContainsString(
                'ext/crawler/Resources/Public/Icons/Extension.svg',
                $expected['items'][0]['icon']
            );
            unset($expected['items'][0]['icon']);
        }

        $actual = $subject->getProcessingInstructions($configuration);
        // Remove the Extension Icon if present, as already tested
        if (!empty($actual['items'][0]['icon'])) {
            unset($actual['items'][0]['icon']);
        }

        self::assertEquals($expected, $actual);
    }

    public static function getProcessingInstructionsDataProvider(): \Iterator
    {
        yield 'All Empty' => [
            'procInstructions' => [],
            'configuration' => [],
            'expected' => [],
        ];

        yield 'procInstructions no configuration' => [
            'procInstructions' => [
                'crawler' => [
                    'key' => 'crawler',
                    'value' => 'Fake Value',
                ],
            ],
            'configuration' => [],
            'expected' => [
                'items' => [
                    [
                        'label' => 'Fake Value [crawler]',
                        'value' => 'crawler',
                        'icon' => 'ext/crawler/Resources/Public/Icons/Extension.svg',
                    ],
                ],
            ],
        ];

        yield 'procInstructions with one configuration' => [
            'procInstructions' => [
                'crawler' => [
                    'key' => 'crawler',
                    'value' => 'Fake Value',
                ],
            ],
            'configuration' => ['default'],
            'expected' => [
                'default',
                'items' => [
                    [
                        'label' => 'Fake Value [crawler]',
                        'value' => 'crawler',
                        'icon' => 'ext/crawler/Resources/Public/Icons/Extension.svg',
                    ],
                ],
            ],
        ];
    }
}
