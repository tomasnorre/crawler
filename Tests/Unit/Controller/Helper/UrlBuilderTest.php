<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller\Helper;

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

use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(UrlBuilder::class)]
final class UrlBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function getBackendModuleUrlBuildsUriForDefaultModuleWithoutRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $uri = new Uri('/typo3/module/web/site/crawler');
        $this->expectUriBuilderCall('web_site_crawler', [
            'foo' => 'bar',
        ], $uri);

        self::assertSame($uri, UrlBuilder::getBackendModuleUrl([
            'foo' => 'bar',
        ]));
    }

    #[Test]
    public function getBackendModuleUrlMergesQueryParametersOfBackendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'id' => 5,
                'foo' => 'from-request',
            ]);
        $uri = new Uri('/typo3/module/other');
        $this->expectUriBuilderCall('other_module', [
            'id' => 5,
            'foo' => 'from-parameters',
            'bar' => 'baz',
        ], $uri);

        self::assertSame($uri, UrlBuilder::getBackendModuleUrl([
            'foo' => 'from-parameters',
            'bar' => 'baz',
        ], 'other_module'));
    }

    #[Test]
    public function getBackendModuleUrlIgnoresQueryParametersOfFrontendRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withQueryParams([
                'id' => 5,
            ]);
        $uri = new Uri('/typo3/module/web/site/crawler');
        $this->expectUriBuilderCall('web_site_crawler', [], $uri);

        self::assertSame($uri, UrlBuilder::getBackendModuleUrl());
    }

    private function expectUriBuilderCall(string $module, array $parameters, Uri $uri): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->expects(self::once())
            ->method('buildUriFromRoute')
            ->with($module, $parameters)
            ->willReturn($uri);
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);
    }
}
