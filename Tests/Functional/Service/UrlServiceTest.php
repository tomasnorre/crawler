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
use AOE\Crawler\Tests\Functional\SiteBasedTestTrait;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class UrlServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
    ];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var UrlService
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(ObjectManager::class)->get(UrlService::class);

        $this->importDataSet(__DIR__ . '/../data/pages.xml');
        $this->importDataSet(__DIR__ . '/../data/sys_template.xml');

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

    /**
     * @test
     * @dataProvider getUrlFromPageAndQueryParametersReturnExpectedUrlDataProvider
     */
    public function getUrlFromPageAndQueryParametersReturnExpectedUrl($pageId, $queryString, $alternativeBaseUrl, $httpsOrHttp, Uri $expected): void
    {
        $actual = $this->subject->getUrlFromPageAndQueryParameters($pageId, $queryString, $alternativeBaseUrl, $httpsOrHttp);

        self::assertEquals(
            $expected->getHost(),
            $actual->getHost()
        );

        self::assertEquals(
            $expected->getScheme(),
            $actual->getScheme()
        );

        self::assertEquals(
            $expected->getPath(),
            $actual->getPath()
        );

        self::assertEquals(
            $expected->getPort(),
            $actual->getPort()
        );

        self::assertContains(
            $expected->getQuery(),
            $actual->getQuery()
        );
    }

    /**
     * @return array[]
     */
    public function getUrlFromPageAndQueryParametersReturnExpectedUrlDataProvider(): array
    {
        $uri = new Uri();

        return [
            'Only with pageId' => [
                'pageId' => 1,
                'queryString' => '',
                'alternativeBaseUrl' => '',
                'httpsOrHttp' => 1,
                'expected' => $uri->withHost('acme.us')->withPath('/')->withScheme('https'),
            ],
            'With PageId and QueryString' => [
                'pageId' => 1,
                'queryString' => 'id=21&q=crawler',
                'alternativeBaseUrl' => '',
                'httpsOrHttp' => 1,
                'expected' => $uri->withHost('acme.us')->withPath('/')->withScheme('https')->withQuery('q=crawler&cHash='),
            ],
            'With PageId and QueryString (including Language (FR))' => [
                'pageId' => 1,
                'queryString' => 'id=21&L=1&q=crawler',
                'alternativeBaseUrl' => '',
                'httpsOrHttp' => 1,
                'expected' => $uri->withHost('acme.fr')->withPath('/')->withScheme('https')->withQuery('q=crawler&cHash='),
            ],
            'With alternative BaseUrl' => [
                'pageId' => 1,
                'queryString' => '',
                'alternativeBaseUrl' => 'https://www.acme.co.uk',
                'httpsOrHttp' => 1,
                'expected' => $uri->withHost('www.acme.co.uk')->withPath('/')->withScheme('https'),
            ],
            'With alternative BaseUrl and port' => [
                'pageId' => 1,
                'queryString' => '',
                'alternativeBaseUrl' => 'https://www.acme.co.uk:443',
                'httpsOrHttp' => 1,
                'expected' => $uri->withHost('www.acme.co.uk')->withPath('/')->withScheme('https')->withPort(443),
            ],
            //TODO: Add tests for when $site !instanceof Site
        ];
    }
}
