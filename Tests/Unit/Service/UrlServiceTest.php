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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\Service\UrlService
 */
class UrlServiceTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var UrlService
     */
    protected $urlService;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->urlService = GeneralUtility::makeInstance(UrlService::class);
    }

    /**
     * @test
     *
     * @dataProvider compileUrlsDataProvider
     */
    public function compileUrls(array $paramArray, array $urls, array $expected, int $expectedCount): void
    {
        $maxUrlsToCompile = 8;

        self::assertEquals(
            $expected,
            $this->urlService->compileUrls($paramArray, $urls, $maxUrlsToCompile)
        );

        self::assertCount(
            $expectedCount,
            $this->urlService->compileUrls($paramArray, $urls, $maxUrlsToCompile)
        );
    }

    public function compileUrlsDataProvider(): iterable
    {
        yield 'Empty Params array' => [
            'paramArray' => [],
            'urls' => ['/home', '/search', '/about'],
            'expected' => ['/home', '/search', '/about'],
            'expectedCount' => 3,
        ];
        yield 'Empty Urls array' => [
            'paramArray' => ['pagination' => [1, 2, 3, 4]],
            'urls' => [],
            'expected' => [],
            'expectedCount' => 0,
        ];
        yield 'case' => [
            'paramArray' => ['pagination' => [1, 2, 3, 4]],
            'urls' => ['index.php?id=10', 'index.php?id=11'],
            'expected' => [
                'index.php?id=10&pagination=1',
                'index.php?id=10&pagination=2',
                'index.php?id=10&pagination=3',
                'index.php?id=10&pagination=4',
                'index.php?id=11&pagination=1',
                'index.php?id=11&pagination=2',
                'index.php?id=11&pagination=3',
                'index.php?id=11&pagination=4',
            ],
            'expectedCount' => 8,
        ];
        yield 'More urls than maximumUrlsToCompile' => [
            'paramArray' => ['pagination' => [1, 2, 3, 4]],
            'urls' => ['index.php?id=10', 'index.php?id=11', 'index.php?id=12'],
            'expected' => [
                'index.php?id=10&pagination=1',
                'index.php?id=10&pagination=2',
                'index.php?id=10&pagination=3',
                'index.php?id=10&pagination=4',
                'index.php?id=11&pagination=1',
                'index.php?id=11&pagination=2',
                'index.php?id=11&pagination=3',
                'index.php?id=11&pagination=4',
            ],
            'expectedCount' => 8,
        ];
    }
}
