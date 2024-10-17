<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

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

use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class UrlServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @noRector
     * @noRector \Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
        ],
        'FR' => [
            'id' => 1,
            'title' => 'French',
            'locale' => 'fr_FR.UTF8',
            'iso' => 'fr',
            'hrefLang' => 'fr-FR',
            'direction' => '',
        ],
        'FR-CA' => [
            'id' => 2,
            'title' => 'Franco-Canadian',
            'locale' => 'fr_CA.UTF8',
            'iso' => 'fr',
            'hrefLang' => 'fr-CA',
            'direction' => '',
        ],
    ];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected UrlService $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(UrlService::class);

        $this->importCSVDataSet(__DIR__ . '/../data/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../data/sys_template.csv');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
    }

    #[DataProvider('getUrlFromPageAndQueryParametersReturnExpectedUrlDataProvider')]
    #[Test]
    public function getUrlFromPageAndQueryParametersReturnExpectedUrl(
        $pageId,
        $queryString,
        $alternativeBaseUrl,
        $httpsOrHttp,
        Uri $expected
    ): void {
        $actual = $this->subject->getUrlFromPageAndQueryParameters(
            $pageId,
            $queryString,
            $alternativeBaseUrl,
            $httpsOrHttp
        );

        self::assertEquals($expected->getHost(), $actual->getHost());

        self::assertEquals($expected->getScheme(), $actual->getScheme());

        self::assertEquals($expected->getPath(), $actual->getPath());

        self::assertEquals($expected->getPort(), $actual->getPort());

        self::assertStringContainsString($expected->getQuery(), $actual->getQuery());

        self::assertEquals($expected->getUserInfo(), $actual->getUserInfo());
    }

    public static function getUrlFromPageAndQueryParametersReturnExpectedUrlDataProvider(): iterable
    {
        $uri = new Uri();

        /* Todo: Look into why we added these tests cases, doesn't look like they make sense any more.
       yield 'Site is not instance of Site::class + http' => [
           'pageId' => 1234,
           'queryString' => '?id=1234&param=foo',
           'alternativeBaseUrl' => 'http://www.example.com',
           'httpsOrHttp' => -1,
           'expected' =>
               $uri->withScheme('http')
                   ->withHost('www.example.com')
                   ->withPath('/pageuid-1234')
                   ->withQuery('param=foo'),
       ];
      yield 'Site is not instance of Site::class + https' => [
           'pageId' => 1234,
           'queryString' => '?id=1234&param=foo',
           'alternativeBaseUrl' => 'https://www.example.com',
           'httpsOrHttp' => 1,
           'expected' =>
               $uri->withScheme('https')
                   ->withHost('www.example.com')
                   ->withPath('/pageuid-1234')
                   ->withQuery('param=foo'),
       ];
       yield 'Site is not instance of Site::class + https + userinfo' => [
           'pageId' => 1234,
           'queryString' => '?id=1234&param=foo',
           'alternativeBaseUrl' => 'https://username:password@www.example.com',
           'httpsOrHttp' => 1,
           'expected' =>
               $uri->withScheme('https')
                   ->withHost('www.example.com')
                   ->withPath('/pageuid-1234')
                   ->withQuery('param=foo')
                   ->withUserInfo('username', 'password'),
       ];
        */
        yield 'Only with pageId' => [
            'pageId' => 1,
            'queryString' => '',
            'alternativeBaseUrl' => '',
            'httpsOrHttp' => 1,
            'expected' =>
                $uri->withScheme('https')
                    ->withHost('acme.us')
                    ->withPath('/'),
        ];
        yield 'With PageId and QueryString' => [
            'pageId' => 1,
            'queryString' => 'id=21&q=crawler',
            'alternativeBaseUrl' => '',
            'httpsOrHttp' => 1,
            'expected' => $uri->withScheme('https')
                ->withHost('acme.us')
                ->withPath('/')
                ->withQuery('q=crawler'),
        ];
        yield 'With PageId and QueryString (including Language (FR))' => [
            'pageId' => 1,
            'queryString' => 'id=21&L=1&q=crawler',
            'alternativeBaseUrl' => '',
            'httpsOrHttp' => 1,
            'expected' =>
                $uri->withScheme('https')
                    ->withHost('acme.fr')
                    ->withPath('/')
                    ->withQuery('q=crawler'),
        ];
        yield 'With alternative BaseUrl' => [
            'pageId' => 1,
            'queryString' => '',
            'alternativeBaseUrl' => 'https://www.acme.co.uk',
            'httpsOrHttp' => 1,
            'expected' =>
                $uri->withScheme('https')
                    ->withHost('www.acme.co.uk')
                    ->withPath('/'),
        ];
        yield 'With alternative BaseUrl and port' => [
            'pageId' => 1,
            'queryString' => '',
            'alternativeBaseUrl' => 'https://www.acme.co.uk:443',
            'httpsOrHttp' => 1,
            'expected' =>
                $uri->withScheme('https')
                    ->withHost('www.acme.co.uk')
                    ->withPath('/')
                    ->withPort(443),
        ];
    }
}
