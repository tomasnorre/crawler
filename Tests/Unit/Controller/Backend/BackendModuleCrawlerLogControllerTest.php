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
}
