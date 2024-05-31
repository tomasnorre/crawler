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
}
