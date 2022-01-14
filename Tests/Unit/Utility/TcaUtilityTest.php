<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Utility;

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
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Utility\TcaUtility
 */
class TcaUtilityTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider getProcessingInstructionsDataProvider
     */
    public function getProcessingInstructions($procInstructions, array $configuration, $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] = $procInstructions;

        $subject =  $this->createPartialMock(TcaUtility::class, ['getExtensionIcon']);
        $subject->expects($this->any())
            ->method('getExtensionIcon')
            ->willReturn('ext/crawler/Resources/Public/Icons/Extension.svg');

        self::assertEquals(
            $expected,
            $subject->getProcessingInstructions($configuration)
        );

    }

    public function getProcessingInstructionsDataProvider(): \Iterator
    {
        $configuration = [];

        yield "All Empty" => [
            'procInstructions' => [],
            'configuration' => [],
            'expected' => [],
        ];

        yield "procInstructions no configuration" => [
            'procInstructions' => [
                'crawler' => [
                    'key' => 'crawler',
                    'value' => 'Fake Value',
                ]
            ],
            'configuration' => [],
            'expected' => [
                'items' => [
                    [
                        'Fake Value [crawler]',
                        'crawler',
                        'ext/crawler/Resources/Public/Icons/Extension.svg'
                    ]
                ]
            ],
        ];

        yield "procInstructions with one configuration" => [
            'procInstructions' => [
                'crawler' => [
                    'key' => 'crawler',
                    'value' => 'Fake Value',
                ]
            ],
            'configuration' => ['default'],
            'expected' => [
                'default',
                'items' => [
                    [
                        'Fake Value [crawler]',
                        'crawler',
                        'ext/crawler/Resources/Public/Icons/Extension.svg'
                    ]
                ]
            ],
        ];
    }
}
