<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy::class)]
class SubProcessExecutionStrategyTest extends UnitTestCase
{
    private const ERROR_PAGE = '<div class="typo3-error-page"><p class="typo3-error-page-statuscode">' . PHP_EOL
        . '404' . PHP_EOL . '</p>'
        . '<h1 class="typo3-error-page-title">' . PHP_EOL . 'Page Not Found' . PHP_EOL . '</h1>'
        . '<p class="typo3-error-page-message">' . PHP_EOL . 'The page did not exist' . PHP_EOL . '</p></div>';

    protected bool $resetSingletonInstances = true;

    private ?string $extensionPath = null;

    private ?PackageManager $originalPackageManager = null;

    protected function tearDown(): void
    {
        if ($this->extensionPath !== null) {
            unlink($this->extensionPath . 'cli/bootstrap.php');
            rmdir($this->extensionPath . 'cli');
            rmdir($this->extensionPath);
        }
        if ($this->originalPackageManager instanceof PackageManager) {
            ExtensionManagementUtility::setPackageManager($this->originalPackageManager);
        }
        unset($GLOBALS['TSFE'], $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler']);
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorTest(): void
    {
        $configuration = [
            'makeDirectRequests' => 0,
            'frontendBasePath' => '/',
        ];

        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionConfiguration')->willReturn($configuration);
        $crawlStrategy = GeneralUtility::makeInstance(
            SubProcessExecutionStrategy::class,
            $extensionConfigurationProvider
        );

        self::assertInstanceOf(SubProcessExecutionStrategy::class, $crawlStrategy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsInvalidSchema(): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $expectedMessage = $typo3Version->getMajorVersion() < 14
            ? 'Scheme does not match for url "/not-an-url"'
            : 'Scheme does not match for url "not-an-url"';
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with($expectedMessage, [
            'crawlerId' => '2981d019ade833a37995c1b569ef87b6b5af7287',
        ]);

        $crawlerId = sha1('this-is-testing');
        $url = new Uri('not-an-url');
        $subProcessExecutionStrategy = $this->createPartialMock(SubProcessExecutionStrategy::class, []);
        $subProcessExecutionStrategy->setLogger($logger);

        self::assertFalse($subProcessExecutionStrategy->fetchUrlContents($url, $crawlerId));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('buildRequestHandlersDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function buildRequestHeadersReturnsArray(array $url, string $crawlerId, array $expected): void
    {
        $subject = new SubProcessExecutionStrategy($this->createStub(ExtensionConfigurationProvider::class));

        self::assertSame(
            $expected,
            (new \ReflectionMethod($subject, 'buildRequestHeaders'))->invoke($subject, $url, $crawlerId)
        );
    }

    public static function buildRequestHandlersDataProvider(): iterable
    {
        $path = '/path/to/page';
        $query = 'q=keyword';
        $host = 'www.example.com';
        $user = 'username';
        $pass = 'password';
        $crawlerId = 'as23dr32a';

        yield 'No Username' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'pass' => $pass,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'No Password' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'user' => $user,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'Username and Password' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'Authorization: Basic ' . base64_encode($user . ':' . $pass),
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'Without query' => [
            'url' => [
                'path' => $path,
                'host' => $host,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsFalseForOtherSchemes(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(
            'Scheme does not match for url "wss://example.com/file"',
            [
                'crawlerId' => 'crawler-id',
            ]
        );
        $subject = new SubProcessExecutionStrategy($this->createStub(ExtensionConfigurationProvider::class));
        $subject->setLogger($logger);

        self::assertFalse($subject->fetchUrlContents(new Uri('wss://example.com/file'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsFalseForUrlWithoutSchemeWithoutLogger(): void
    {
        $subject = new SubProcessExecutionStrategy($this->createStub(ExtensionConfigurationProvider::class));

        self::assertFalse($subject->fetchUrlContents(new Uri('//example.com/'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fetchUrlContentsCallsCliBootstrapDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsCallsCliBootstrapWithUrlAndRequestHeaders(
        string $url,
        array $expectedHeaders
    ): void {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('debug');
        $logger->expects(self::once())->method('info')->willReturnCallback(
            static function (string $message) use ($url): void {
                self::assertMatchesRegularExpression(
                    '/^' . preg_quote($url, '/') . ' \\d+(\\.\\d+)?(E-\\d+)?$/',
                    $message
                );
                self::assertLessThan(60, (float) substr($message, strlen($url) + 1));
            }
        );
        $subject = $this->createSubjectWithCliBootstrap();
        $subject->setLogger($logger);

        $result = $subject->fetchUrlContents(new Uri($url), 'crawler-id');

        self::assertIsArray($result);
        self::assertSame(['content'], array_keys($result));
        $arguments = json_decode((string) $result['content'], true);
        self::assertSame('/', $arguments[1]);
        self::assertSame($url, $arguments[2]);
        self::assertSame($expectedHeaders, unserialize(base64_decode((string) $arguments[3], true)));
    }

    public static function fetchUrlContentsCallsCliBootstrapDataProvider(): iterable
    {
        yield 'http' => [
            'http://example.com/page?id=1',
            [
                'GET /page?id=1 HTTP/1.0',
                'Host: example.com',
                'Connection: close',
                'X-T3crawler: crawler-id',
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'https with user info' => [
            'https://user:secret@example.com/page',
            [
                'GET /page HTTP/1.0',
                'Host: example.com',
                'Connection: close',
                'Authorization: Basic ' . base64_encode('user:secret'),
                'X-T3crawler: crawler-id',
                'User-Agent: TYPO3 crawler',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsErrorOfErrorPage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(
            'Error while opening "https://example.com/error" - 404 Page Not Found - The page did not exist',
            [
                'crawlerId' => 'crawler-id',
            ]
        );
        $subject = $this->createSubjectWithCliBootstrap();
        $subject->setLogger($logger);

        $result = $subject->fetchUrlContents(new Uri('https://example.com/error'), 'crawler-id');

        self::assertSame([
            'errorlog' => ['404 Page Not Found - The page did not exist'],
            'content' => self::ERROR_PAGE,
        ], $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsErrorOfErrorPageWithoutLogger(): void
    {
        $result = $this->createSubjectWithCliBootstrap()->fetchUrlContents(
            new Uri('https://example.com/error'),
            'crawler-id'
        );

        self::assertIsArray($result);
        self::assertSame(['404 Page Not Found - The page did not exist'], $result['errorlog']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsFalseWithoutOutput(): void
    {
        $subject = $this->createSubjectWithCliBootstrap();

        self::assertFalse($subject->fetchUrlContents(new Uri('https://example.com/empty'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('frontendBasePathDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsHandsOverFrontendBasePath(
        array $extensionSettings,
        ?string $absRefPrefix,
        string $expected
    ): void {
        if ($absRefPrefix !== null) {
            $GLOBALS['TSFE'] = new \stdClass();
            $GLOBALS['TSFE']->absRefPrefix = $absRefPrefix;
        }
        $subject = $this->createSubjectWithCliBootstrap($extensionSettings);

        $result = $subject->fetchUrlContents(new Uri('https://example.com/'), 'crawler-id');

        self::assertIsArray($result);
        self::assertSame($expected, json_decode((string) $result['content'], true)[1]);
    }

    public static function frontendBasePathDataProvider(): iterable
    {
        yield 'Default' => [[], null, '/'];
        yield 'Empty extension setting' => [[
            'frontendBasePath' => '',
        ], null, '/'];
        yield 'Extension setting' => [[
            'frontendBasePath' => 'cms',
        ], null, '/cms/'];
        yield 'Extension setting with slashes' => [[
            'frontendBasePath' => '//cms/sub//',
        ], '/other/', '/cms/sub/'];
        yield 'Extension setting is an integer' => [[
            'frontendBasePath' => 5,
        ], null, '/5/'];
        yield 'absRefPrefix' => [[], 'other', '/other/'];
        yield 'Empty absRefPrefix' => [[], '', '/'];
    }

    /**
     * Creates the strategy with a cli/bootstrap.php, that outputs its arguments as JSON,
     * the error page for URLs ending with "error" and nothing for URLs ending with "empty".
     */
    private function createSubjectWithCliBootstrap(array $extensionSettings = []): SubProcessExecutionStrategy
    {
        $this->extensionPath = sys_get_temp_dir() . '/crawler-subprocess-test-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->extensionPath . 'cli', 0o777, true);
        file_put_contents(
            $this->extensionPath . 'cli/bootstrap.php',
            '<?php' . PHP_EOL
            . 'if (str_ends_with($argv[2], "empty")) { exit; }' . PHP_EOL
            . 'echo str_ends_with($argv[2], "error") ? ' . var_export(self::ERROR_PAGE, true)
            . ' : json_encode($argv);' . PHP_EOL
        );

        $package = $this->createStub(PackageInterface::class);
        $package->method('getPackagePath')->willReturn($this->extensionPath);
        $packageManager = $this->createStub(PackageManager::class);
        $packageManager->method('isPackageActive')->willReturnCallback(
            static fn (string $key): bool => $key === 'crawler'
        );
        $packageManager->method('getPackage')->willReturn($package);
        $packageManagerProperty = new \ReflectionProperty(ExtensionManagementUtility::class, 'packageManager');
        if ($packageManagerProperty->isInitialized()) {
            $this->originalPackageManager = $packageManagerProperty->getValue();
        }
        ExtensionManagementUtility::setPackageManager($packageManager);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpPath' => PHP_BINARY,
        ];
        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionConfiguration')->willReturn($extensionSettings);

        return new SubProcessExecutionStrategy($extensionConfigurationProvider);
    }
}
