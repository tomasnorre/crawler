<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Unit\Backend\Helper;

use AOE\Crawler\Backend\Helper\ResultHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Backend\Helper\ResultHandler
 * @covers \AOE\Crawler\Converter\JsonCompatibilityConverter::convert
 */
class ResultHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider getResStatusDataProvider
     */
    public function getResStatus($requestContent, string $expected): void
    {
        self::assertSame(
            $expected,
            ResultHandler::getResStatus($requestContent)
        );
    }

    public function getResStatusDataProvider(): array
    {
        return [
            'requestContent is not array' => [
                'requestContent' => null,
                'expected' => '-',
            ],
            'requestContent is empty array' => [
                'requestContent' => [],
                'expected' => '-',
            ],
            'requestContent["content"] index does not exist' => [
                'requestContent' => ['not-content' => 'value'],
                'expected' => 'Content index does not exists in requestContent array',
            ],
            'errorlog is present but empty' => [
                'requestContent' => ['content' => json_encode(['errorlog' => []], JSON_THROW_ON_ERROR)],
                'expected' => 'OK',
            ],
            'errorlog is present and not empty (1 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server error']], JSON_THROW_ON_ERROR)],
                'expected' => '500 Internal Server error',
            ],
            'errorlog is present and not empty (2 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server Error', '503 Service Unavailable']], JSON_THROW_ON_ERROR)],
                'expected' => '500 Internal Server Error' . chr(10) . '503 Service Unavailable',
            ],
            'requestResult is boolean' => [
                'requestContent' => ['content' => 'This string is neither json or serialized, therefor convert returns false'],
                'expected' => 'Error - no info, sorry!',
            ],
            // Missing test case for the return 'Error: ' (last return)

        ];
    }

    /**
     * @test
     * @dataProvider getResFeVarsDataProvider
     */
    public function getResFeVars(array $resultData, array $expected): void
    {
        self::assertSame(
            $expected,
            ResultHandler::getResFeVars($resultData)
        );
    }

    public function getResFeVarsDataProvider(): array
    {
        return [
            'ResultData is empty, therefore empty array returned' => [
                'resultData' => [],
                'expected' => [],
            ],
            'result data does not contain vars' => [
                'resultData' => [
                    'content' => json_encode(['not-vars' => 'some value'], JSON_THROW_ON_ERROR),
                ],
                'expected' => [],
            ],
            'Result data vars is present by empty, therefore empty array is returned' => [
                'resultData' => [
                    'content' => json_encode(['vars' => []], JSON_THROW_ON_ERROR),
                ],
                'expected' => [],
            ],
            'Result data vars is present and not empty' => [
                'resultData' => [
                    'content' => json_encode(['vars' => ['fe-one', 'fe-two']], JSON_THROW_ON_ERROR),
                ],
                'expected' => ['fe-one', 'fe-two'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getResultLogDataProvider
     */
    public function getResultLog(array $resultLog, string $expected): void
    {
        self::assertSame(
            $expected,
            ResultHandler::getResultLog($resultLog)
        );
    }

    public function getResultLogDataProvider(): array
    {
        return [
            'ResultRow key result_data does not exist' => [
                'resultRow' => [
                    'other-key' => 'value',
                ],
                'expected' => '',
            ],
            'ResultRow key result_data does exist, but empty' => [
                'resultRow' => [
                    'result_data' => '',
                ],
                'expected' => '',
            ],
            /* Bug We don't handle when result row doesn't contain content key */
            'ResultRow key result_data exits, is not empty, but does not contain content key' => [
                'resultRow' => [
                    'result_data' => json_encode(['not-content' => 'value'], JSON_THROW_ON_ERROR),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does not contain log' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['not-log' => ['ok']], JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (1 element)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok']], JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR),
                ],
                'expected' => 'ok',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (2 elements)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok', 'success']], JSON_THROW_ON_ERROR)], JSON_THROW_ON_ERROR),
                ],
                'expected' => 'ok' . chr(10) . 'success',
            ],
        ];
    }
}
