<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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

use AOE\Crawler\Service\BackendModuleScriptUrlService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(BackendModuleScriptUrlService::class)]
class BackendModuleScriptUrlServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[DataProvider('buildScriptUrlDataProvider')]
    #[Test]
    public function buildScriptUrlReplacesValueOfElementInQueryParameters(
        array $queryParameters,
        string $queryString,
        string $expected
    ): void {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->expects(self::once())->method('buildUriFromRoute')->with('web_site_crawler_log', [
            'id' => 5,
        ])->willReturn(new Uri('/typo3/module/crawler/log?id=5'));
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);
        $request = (new ServerRequest())->withAttribute('route', new Route('/module/crawler/log', [
            '_identifier' => 'web_site_crawler_log',
        ]));

        self::assertSame(
            $expected,
            (new BackendModuleScriptUrlService())->buildScriptUrl(
                $request,
                'displayLog',
                5,
                $queryParameters,
                $queryString
            )
        );
    }

    public static function buildScriptUrlDataProvider(): iterable
    {
        yield 'No query parameters' => [[], '', '/typo3/module/crawler/log?id=5&displayLog=${value}'];
        yield 'Element is removed from the query parameters' => [
            [
                'setID' => 12,
                'displayLog' => 'all',
                'itemsPerPage' => 10,
            ],
            '&qid_details=3',
            '/typo3/module/crawler/log?id=5&qid_details=3&setID=12&itemsPerPage=10&displayLog=${value}',
        ];
    }
}
