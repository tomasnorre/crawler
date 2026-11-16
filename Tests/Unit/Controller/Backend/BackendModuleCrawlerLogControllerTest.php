<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller\Backend;

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

use AOE\Crawler\Controller\Backend\BackendModuleCrawlerLogController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(BackendModuleCrawlerLogController::class)]
class BackendModuleCrawlerLogControllerTest extends UnitTestCase
{
    /**
     * The set id is handed on to the links of the log filters, so changing a filter keeps the selected set.
     */
    #[DataProvider('setIdDataProvider')]
    #[Test]
    public function readsTheSetIdFromTheRequest(ServerRequest $request, int $expectedSetId): void
    {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();

        (new \ReflectionMethod($subject, 'setPropertiesBasedOnPostVars'))->invoke($subject, $request);

        self::assertSame($expectedSetId, (new \ReflectionProperty($subject, 'setId'))->getValue($subject));
    }

    public static function setIdDataProvider(): iterable
    {
        yield 'Set id in the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
                'setID' => 123,
            ]),
            'expectedSetId' => 123,
        ];
        yield 'Set id in the parsed body' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
            ])->withParsedBody([
                'setID' => 456,
            ]),
            'expectedSetId' => 456,
        ];
        yield 'No set id' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
            ]),
            'expectedSetId' => 0,
        ];
    }

    #[DataProvider('requestPropertiesDataProvider')]
    #[Test]
    public function readsThePropertiesFromTheRequest(ServerRequest $request, array $expectedProperties): void
    {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();

        (new \ReflectionMethod($subject, 'setPropertiesBasedOnPostVars'))->invoke($subject, $request);

        $properties = [];
        foreach (array_keys($expectedProperties) as $propertyName) {
            $properties[$propertyName] = (new \ReflectionProperty($subject, $propertyName))->getValue($subject);
        }
        self::assertSame($expectedProperties, $properties);
    }

    public static function requestPropertiesDataProvider(): iterable
    {
        yield 'Defaults' => [
            'request' => new ServerRequest(),
            'expectedProperties' => [
                'pageUid' => -1,
                'quiPath' => '',
                'queueId' => null,
                'logDisplay' => 'all',
                'itemsPerPage' => 10,
                'showResultLog' => '0',
                'showFeVars' => '0',
                'logDepth' => '0',
            ],
        ];
        yield 'Values in the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => '5',
                'qid_details' => '12abc',
                'displayLog' => 'finished',
                'itemsPerPage' => '50',
                'ShowResultLog' => '1',
                'ShowFeVars' => '1',
                'logDepth' => '2',
            ]),
            'expectedProperties' => [
                'pageUid' => 5,
                'quiPath' => '&qid_details=12',
                'queueId' => '12abc',
                'logDisplay' => 'finished',
                'itemsPerPage' => 50,
                'showResultLog' => '1',
                'showFeVars' => '1',
                'logDepth' => '2',
            ],
        ];
        yield 'Queue id in the parsed body wins over the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '12',
            ])->withParsedBody([
                'qid_details' => '13',
            ]),
            'expectedProperties' => [
                'quiPath' => '&qid_details=13',
                'queueId' => '13',
            ],
        ];
        yield 'Parsed body is an object' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '12',
            ])->withParsedBody(new \stdClass()),
            'expectedProperties' => [
                'quiPath' => '&qid_details=12',
                'queueId' => '12',
            ],
        ];
        yield 'No queue id' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '0',
            ]),
            'expectedProperties' => [
                'quiPath' => '',
                'queueId' => '0',
            ],
        ];
    }
}
