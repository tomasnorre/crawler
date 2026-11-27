<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2021 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Service\UrlService;
use Doctrine\DBAL\Result;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\UrlService::class)]
class UrlServiceTest extends UnitTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Service\UrlService $urlService;
    protected bool $resetSingletonInstances = true;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->urlService = GeneralUtility::makeInstance(UrlService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('compileUrlsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function compileUrls(array $paramArray, array $urls, array $expected, int $expectedCount): void
    {
        self::assertEquals($expected, $this->urlService->compileUrls($paramArray, $urls));

        self::assertCount($expectedCount, $this->urlService->compileUrls($paramArray, $urls));
    }

    public static function compileUrlsDataProvider(): iterable
    {
        yield 'Empty Params array' => [
            'paramArray' => [],
            'urls' => ['/home', '/search', '/about'],
            'expected' => ['/home', '/search', '/about'],
            'expectedCount' => 3,
        ];
        yield 'Empty Urls array' => [
            'paramArray' => [
                'pagination' => [1, 2, 3, 4],
            ],
            'urls' => [],
            'expected' => [],
            'expectedCount' => 0,
        ];
        yield 'Exactly number of URLs matches maximumUrlsToCompile' => [
            'paramArray' => [
                'pagination' => [1, 2, 3, 4, 5],
            ],
            'urls' => ['index.php?id=10', 'index.php?id=11'],
            'expected' => [
                'index.php?id=10&pagination=1',
                'index.php?id=10&pagination=2',
                'index.php?id=10&pagination=3',
                'index.php?id=10&pagination=4',
                'index.php?id=10&pagination=5',
                'index.php?id=11&pagination=1',
                'index.php?id=11&pagination=2',
                'index.php?id=11&pagination=3',
                'index.php?id=11&pagination=4',
                'index.php?id=11&pagination=5',
            ],
            'expectedCount' => 10,
        ];
        yield 'More urls than maximumUrlsToCompile' => [
            'paramArray' => [
                'pagination' => [1, 2, 3, 4, 5],
            ],
            'urls' => ['index.php?id=10', 'index.php?id=11', 'index.php?id=12'],
            'expected' => [
                'index.php?id=10&pagination=1',
                'index.php?id=10&pagination=2',
                'index.php?id=10&pagination=3',
                'index.php?id=10&pagination=4',
                'index.php?id=10&pagination=5',
                'index.php?id=11&pagination=1',
                'index.php?id=11&pagination=2',
                'index.php?id=11&pagination=3',
                'index.php?id=11&pagination=4',
                'index.php?id=11&pagination=5',
            ],
            'expectedCount' => 10,
        ];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function compileUrlsEncodesParametersAndSkipsEmptyValues(): void
    {
        self::assertSame(
            ['?id=1&0=a%20b&tx%5Bpage%5D=1', '?id=1&0=a%20b', '?id=1&tx%5Bpage%5D=1', '?id=1'],
            $this->urlService->compileUrls([
                0 => ['a b', ''],
                'tx[page]' => [1, ''],
            ], ['?id=1'])
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function compileUrlsLimitsNumberOfUrls(): void
    {
        self::assertSame(
            ['?id=1&L=0', '?id=1&L=1', '?id=1&L=2'],
            $this->urlService->compileUrls([
                'L' => [0, 1, 2, 3],
            ], ['?id=1'], 3)
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUrlFromPageWithoutSiteDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlFromPageAndQueryParametersReturnsUrlWithSchemeOnlyWithoutSite(
        int $httpsOrHttp,
        string $expected
    ): void {
        $siteMatcher = $this->createMock(SiteMatcher::class);
        $siteMatcher->expects(self::once())->method('matchByPageId')->with(5)->willReturn(new NullSite());
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcher);

        $url = $this->urlService->getUrlFromPageAndQueryParameters(5, '&L=1', 'https://base.example.com', $httpsOrHttp);

        self::assertSame($expected, (string) $url);
    }

    public static function getUrlFromPageWithoutSiteDataProvider(): iterable
    {
        yield 'Keep scheme' => [0, ''];
        yield 'Force http' => [-1, 'http:'];
        yield 'Force https' => [1, 'https:'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUrlFromPageWithSiteDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlFromPageAndQueryParametersBuildsUrlWithRouterOfSite(
        string $queryString,
        ?string $alternativeBaseUrl,
        int $httpsOrHttp,
        array $expectedParameters,
        string $expected
    ): void {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())->method('generateUri')->with(5, $expectedParameters)->willReturn(
            new Uri('https://example.com/page?' . http_build_query($expectedParameters))
        );
        $site = $this->createStub(Site::class);
        $site->method('getRouter')->willReturn($router);
        $siteMatcher = $this->createStub(SiteMatcher::class);
        $siteMatcher->method('matchByPageId')->willReturn($site);
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcher);

        $url = $this->urlService->getUrlFromPageAndQueryParameters(5, $queryString, $alternativeBaseUrl, $httpsOrHttp);

        self::assertSame($expected, (string) $url);
    }

    public static function getUrlFromPageWithSiteDataProvider(): iterable
    {
        yield 'Page id is removed from query' => [
            '?id=5&foo=bar',
            null,
            0,
            [
                'foo' => 'bar',
            ],
            'https://example.com/page?foo=bar',
        ];
        yield 'Default language is handed over as _language' => [
            '&L=0',
            '',
            0,
            [
                '_language' => '0',
            ],
            'https://example.com/page?_language=0',
        ];
        yield 'Alternative base URL' => [
            '&foo=bar',
            'http://user:secret@base.example.com:8080',
            0,
            [
                'foo' => 'bar',
            ],
            'http://user:secret@base.example.com:8080/page?foo=bar',
        ];
        yield 'Alternative base URL without user info and port' => [
            '&foo=bar',
            'http://base.example.com',
            1,
            [
                'foo' => 'bar',
            ],
            'https://base.example.com/page?foo=bar',
        ];
        yield 'Force http' => ['', null, -1, [], 'http://example.com/page'];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlFromPageAndQueryParametersThrowsExceptionForUnknownLanguage(): void
    {
        $site = $this->createStub(Site::class);
        $site->method('getLanguageById')->willThrowException(new \InvalidArgumentException('', 1522960188));
        $siteMatcher = $this->createStub(SiteMatcher::class);
        $siteMatcher->method('matchByPageId')->willReturn($site);
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcher);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1522960188);

        $this->urlService->getUrlFromPageAndQueryParameters(5, '&L=0', null, 0);
    }

    /**
     * A URL of a translation is only built if the page, or its translation into the language
     * or one of its fallback languages, exists.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pageExistsInLanguageDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlFromPageAndQueryParametersChecksThatThePageExistsInTheLanguage(
        array|false $row,
        ?string $expected
    ): void {
        $siteLanguage = $this->createStub(SiteLanguage::class);
        $siteLanguage->method('getFallbackLanguageIds')->willReturn([0]);
        $router = $this->createStub(RouterInterface::class);
        $router->method('generateUri')->willReturn(new Uri('https://example.com/fr/page'));
        $site = $this->createMock(Site::class);
        $site->method('getLanguageById')->with(1)->willReturn($siteLanguage);
        $site->method('getRouter')->willReturn($router);
        $siteMatcher = $this->createStub(SiteMatcher::class);
        $siteMatcher->method('matchByPageId')->willReturn($site);
        GeneralUtility::setSingletonInstance(SiteMatcher::class, $siteMatcher);

        $result = $this->createStub(Result::class);
        $result->method('fetchAssociative')->willReturn($row);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects(self::once())->method('in')->with('sys_language_uid', [1, 0])->willReturn('');
        $expressionBuilder->method('eq')->willReturn('');
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);
        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);

        $url = $this->urlService->getUrlFromPageAndQueryParameters(5, '&L=1', null, 0);

        self::assertSame($expected, $url instanceof UriInterface ? (string) $url : null);
    }

    public static function pageExistsInLanguageDataProvider(): iterable
    {
        yield 'Page exists in the language' => [
            [
                'uid' => 5,
            ],
            'https://example.com/fr/page',
        ];
        yield 'Page does not exist in the language' => [false, null];
    }
}
