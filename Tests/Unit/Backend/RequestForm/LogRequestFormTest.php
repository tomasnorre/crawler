<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Backend\RequestForm;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Backend\RequestForm\LogRequestForm;
use TYPO3\CMS\Core\Localization\LanguageService;

class LogRequestFormTest extends \Nimut\TestingFramework\TestCase\UnitTestCase
{
    /**
     * @var LogRequestForm
     */
    protected $subject;

    protected function setUp(): void
    {
        /*$mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->expects($this->any())->method('sL')->willReturn('language string');

        $this->subject = self::getAccessibleMock(LogRequestForm::class, ['getLanguageService'], [], '', false);
        $this->subject->expects($this->any())->method('getLanguageService')->willReturn($mockedLanguageService);

        $jsonCompatibilityConverter = new JsonCompatibilityConverter();
        $this->subject->_set('jsonCompatibilityConverter', $jsonCompatibilityConverter);
        */
    }

    /**
     * test
     * @dataProvider getResStatusDataProvider
     */
    public function getResStatus($requestContent, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResStatus', $requestContent)
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
                'requestContent' => ['content' => json_encode(['errorlog' => []])],
                'expected' => 'OK',
            ],
            'errorlog is present and not empty (1 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server error']])],
                'expected' => '500 Internal Server error',
            ],
            'errorlog is present and not empty (2 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server Error', '503 Service Unavailable']])],
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
     * test
     * @dataProvider getResFeVarsDataProvider
     */
    public function getResFeVars(array $resultData, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResFeVars', $resultData)
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
                    'content' => json_encode(['not-vars' => 'some value']),
                ],
                'expected' => [],
            ],
            'Result data vars is present by empty, therefore empty array is returned' => [
                'resultData' => [
                    'content' => json_encode(['vars' => []]),
                ],
                'expected' => [],
            ],
            'Result data vars is present and not empty' => [
                'resultData' => [
                    'content' => json_encode(['vars' => ['fe-one', 'fe-two']]),
                ],
                'expected' => ['fe-one', 'fe-two'],
            ],
        ];
    }

    /**
     * test
     * @dataProvider getResultLogDataProvider
     */
    public function getResultLog(array $resultLog, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResultLog', $resultLog)
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
                    'result_data' => json_encode(['not-content' => 'value']),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does not contain log' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['not-log' => ['ok']])]),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (1 element)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok']])]),
                ],
                'expected' => 'ok',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (2 elements)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok', 'success']])]),
                ],
                'expected' => 'ok' . chr(10) . 'success',
            ],
        ];
    }
}
