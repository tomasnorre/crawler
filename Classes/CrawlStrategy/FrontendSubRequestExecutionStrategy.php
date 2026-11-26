<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\Interface\CrawlStrategyInterface;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\AspectInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Http\Application;

/**
 * Renders a page by handing a request to the TYPO3 frontend application within the current process,
 * like the TYPO3 Core does for error pages (see PageContentErrorHandler). No HTTP request and no
 * PHP sub-process are needed.
 *
 * The crawler runs this in a long-living worker (messenger:consume), so the global state the frontend
 * changes is restored after each request.
 *
 * @internal since v14.0.0
 *
 * @psalm-api Created by the CrawlStrategyFactory through its service locator
 */
final class FrontendSubRequestExecutionStrategy implements LoggerAwareInterface, CrawlStrategyInterface
{
    use LoggerAwareTrait;

    private const MAX_REDIRECTS = 5;

    /**
     * Context aspects the frontend application sets, which are restored after each request
     */
    private const CONTEXT_ASPECTS = ['date', 'visibility', 'workspace', 'backend.user', 'frontend.user', 'language'];

    /**
     * The time globals of TYPO3, with the seconds they are rounded to.
     * TYPO3 sets them once, when the worker process starts.
     */
    private const REQUEST_TIME_GLOBALS = [
        'EXEC_TIME' => 1,
        'SIM_EXEC_TIME' => 1,
        'ACCESS_TIME' => 60,
        'SIM_ACCESS_TIME' => 60,
    ];

    private const GLOBALS_TO_RESTORE = [
        'TYPO3_REQUEST',
        'EXEC_TIME',
        'SIM_EXEC_TIME',
        'ACCESS_TIME',
        'SIM_ACCESS_TIME',
    ];

    public function __construct(
        private readonly Application $frontendApplication,
        private readonly Context $context,
        private readonly PageRenderer $pageRenderer,
        private readonly ExtensionConfigurationProvider $configurationProvider,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array|false See CrawlStrategyInterface::fetchUrlContents()
     */
    #[\Override]
    public function fetchUrlContents(UriInterface $url, string $crawlerId): array|false
    {
        if (!$this->isHttpUrl($url)) {
            $this->logger?->debug(sprintf('Scheme does not match for url "%s"', $url), [
                'crawlerId' => $crawlerId,
            ]);
            return false;
        }

        try {
            $response = $this->fetchFollowingRedirects($url, $crawlerId);
        } catch (\Throwable $exception) {
            return $this->buildErrorResult($url, $crawlerId, $exception->getMessage());
        }
        if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
            return $this->buildErrorResult(
                $url,
                $crawlerId,
                sprintf('Too many redirects, gave up after %d', self::MAX_REDIRECTS)
            );
        }

        return $this->buildResult($response, $url, $crawlerId);
    }

    /**
     * @return ResponseInterface|null null if there are more redirects than MAX_REDIRECTS
     */
    private function fetchFollowingRedirects(UriInterface $url, string $crawlerId): ?ResponseInterface
    {
        $redirects = 0;
        do {
            $response = $this->handleIsolated($this->buildRequest($url, $crawlerId));
            $location = $this->getRedirectLocation($response, $url);
            if (!$location instanceof \Psr\Http\Message\UriInterface) {
                return $response;
            }
            $url = $location;
        } while (++$redirects <= self::MAX_REDIRECTS);

        return null;
    }

    private function getRedirectLocation(ResponseInterface $response, UriInterface $url): ?UriInterface
    {
        $status = $response->getStatusCode();
        $location = $response->getHeaderLine('Location');
        if ($status < 300 || $status >= 400 || $location === '') {
            return null;
        }
        try {
            $target = new Uri($location);
        } catch (\InvalidArgumentException) {
            return null;
        }
        if ($target->getHost() === '') {
            $target = $target->withScheme($url->getScheme())->withHost($url->getHost())->withPort($url->getPort());
        }
        return $this->isHttpUrl($target) ? $target : null;
    }

    private function buildRequest(UriInterface $url, string $crawlerId): ServerRequestInterface
    {
        $headers = [
            'X-T3Crawler' => $crawlerId,
            'User-Agent' => 'TYPO3 crawler',
        ];
        if ($url->getUserInfo() !== '') {
            $headers['Authorization'] = 'Basic ' . base64_encode(rawurldecode($url->getUserInfo()));
        }
        $url = $url->withUserInfo('');
        parse_str($url->getQuery(), $queryParams);

        return (new ServerRequest($url, 'GET', 'php://temp', $headers, $this->buildServerParams($url)))
            ->withQueryParams($queryParams)
            // The frontend application sets it as well, the page renderer needs it before
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    /**
     * The server parameters the frontend reads through NormalizedParams
     */
    private function buildServerParams(UriInterface $url): array
    {
        $isHttps = $url->getScheme() === 'https';
        $port = $url->getPort();
        $scriptName = $this->getFrontendBasePath() . 'index.php';
        $requestUri = rtrim($url->getPath() . '?' . $url->getQuery(), '?');

        return [
            // The URL has no user info anymore, so the authority is the host and a non-standard port
            'HTTP_HOST' => $url->getAuthority(),
            'SERVER_NAME' => $url->getHost(),
            'SERVER_PORT' => (string) ($port ?? ($isHttps ? 443 : 80)),
            'HTTPS' => $isHttps ? 'on' : 'off',
            'SCRIPT_NAME' => $scriptName,
            'PHP_SELF' => $scriptName,
            'SCRIPT_FILENAME' => Environment::getPublicPath() . '/index.php',
            'REQUEST_URI' => $requestUri,
            'QUERY_STRING' => $url->getQuery(),
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TYPO3 crawler',
        ];
    }

    private function handleIsolated(ServerRequestInterface $request): ResponseInterface
    {
        $globals = $this->stashGlobals();
        $aspects = $this->stashContextAspects();
        $this->setRequestTime();
        $this->pageRenderer->reset($request);
        try {
            return $this->frontendApplication->handle($request);
        } finally {
            $this->pageRenderer->reset($request);
            $this->restoreContextAspects($aspects);
            $this->restoreGlobals($globals);
        }
    }

    /**
     * A worker lives longer than a web request, the frontend needs the time of the crawl
     * for start and end times of records and the cache lifetime.
     */
    private function setRequestTime(): void
    {
        $now = $this->clock->now()->getTimestamp();
        foreach (self::REQUEST_TIME_GLOBALS as $name => $seconds) {
            $GLOBALS[$name] = $now - $now % $seconds;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stashGlobals(): array
    {
        $globals = [];
        foreach (self::GLOBALS_TO_RESTORE as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $globals[$name] = $GLOBALS[$name];
            }
        }
        return $globals;
    }

    /**
     * @param array<string, mixed> $globals
     */
    private function restoreGlobals(array $globals): void
    {
        foreach (self::GLOBALS_TO_RESTORE as $name) {
            unset($GLOBALS[$name]);
        }
        foreach ($globals as $name => $value) {
            $GLOBALS[$name] = $value;
        }
    }

    /**
     * @return array<string, AspectInterface>
     */
    private function stashContextAspects(): array
    {
        $aspects = [];
        foreach (self::CONTEXT_ASPECTS as $name) {
            $aspects[$name] = $this->context->getAspect($name);
        }
        return $aspects;
    }

    /**
     * @param array<string, AspectInterface> $aspects
     */
    private function restoreContextAspects(array $aspects): void
    {
        foreach ($aspects as $name => $aspect) {
            $this->context->setAspect($name, $aspect);
        }
    }

    private function buildResult(ResponseInterface $response, UriInterface $url, string $crawlerId): array
    {
        $status = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        if ($response->getStatusCode() >= 400) {
            return $this->buildErrorResult($url, $crawlerId, $status);
        }

        $meta = json_decode($response->getHeaderLine('X-T3Crawler-Meta'), true);
        if (is_array($meta)) {
            return $meta;
        }

        return [
            'errorlog' => ['Response has no X-T3Crawler-Meta header'],
            'vars' => [
                'status' => $status,
            ],
        ];
    }

    private function buildErrorResult(UriInterface $url, string $crawlerId, string $message): array
    {
        $this->logger?->debug(sprintf('Error while opening "%s" - %s', $url, $message), [
            'crawlerId' => $crawlerId,
        ]);
        return [
            'errorlog' => [$message],
        ];
    }

    private function isHttpUrl(UriInterface $url): bool
    {
        return in_array($url->getScheme(), ['http', 'https'], true) && $url->getHost() !== '';
    }

    /**
     * The base path of the website frontend, e.g. "/cms/" if the frontend is called by
     * http://mydomain.com/cms/index.php in the browser.
     */
    private function getFrontendBasePath(): string
    {
        $frontendBasePath = trim(
            (string) ($this->configurationProvider->getExtensionConfiguration()['frontendBasePath'] ?? ''),
            '/'
        );
        return $frontendBasePath === '' ? '/' : '/' . $frontendBasePath . '/';
    }
}
