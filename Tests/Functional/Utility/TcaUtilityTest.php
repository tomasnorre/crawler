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

/**
 * @covers \AOE\Crawler\Utility\TcaUtility
 */
class TcaUtilityTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @test
     * @dataProvider getProcessingInstructionsDataProvider
     */
    public function getProcessingInstructions($procInstructions, array $configuration, $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = $procInstructions;

        $subject = new TcaUtility();

        if (!empty($expected['items'][0][2])) {
            self::assertStringContainsString(
                'ext/crawler/Resources/Public/Icons/Extension.svg',
                $expected['items'][0][2]
            );
            unset($expected['items'][0][2]);
        }

        $actual = $subject->getProcessingInstructions($configuration);
        // Remove the Extension Icon if present, as already tested
        if (!empty($actual['items'][0][2])) {
            unset($actual['items'][0][2]);
        }

        self::assertEquals($expected, $actual);
    }

    public function getProcessingInstructionsDataProvider(): \Iterator
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
                    ['Fake Value [crawler]', 'crawler', 'ext/crawler/Resources/Public/Icons/Extension.svg'],
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
                    ['Fake Value [crawler]', 'crawler', 'ext/crawler/Resources/Public/Icons/Extension.svg'],
                ],
            ],
        ];
    }
}
