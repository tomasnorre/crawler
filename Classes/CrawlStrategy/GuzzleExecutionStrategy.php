<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls Guzzle / CURL (based on TYPO3 settings) for fetching a URL.
 * @internal since v12.0.0
 */
class GuzzleExecutionStrategy implements LoggerAwareInterface, CrawlStrategyInterface
{
    use LoggerAwareTrait;

    /**
     * Sets up a CURL / Guzzle Request for fetching the request.
     *
     * @return array|false See CrawlStrategyInterface::fetchUrlContents()
     */
    public function fetchUrlContents(UriInterface $url, string $crawlerId)
    {
        $reqHeaders = $this->buildRequestHeaders($crawlerId);

        $options = [
            'headers' => $reqHeaders,
            'connect_timeout' => 5.0,
        ];
        if ($url->getUserInfo()) {
            $options['auth'] = explode(':', $url->getUserInfo());
        }
        try {
            $url = (string) $url;
            $response = $this->getResponse($url, $options);
            if ($response->hasHeader('X-T3Crawler-Meta')) {
                return unserialize($response->getHeaderLine('X-T3Crawler-Meta'));
            }
            return [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(),
                ],
            ];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $message = ($response ? $response->getStatusCode() : 0)
                . chr(32)
                . ($response ? $response->getReasonPhrase() : $e->getMessage());

            $this->logger->debug(
                sprintf('Error while opening "%s" - ' . $message, $url),
                [
                    'crawlerId' => $crawlerId,
                ]
            );
            return [
                'errorlog' => [$message],
            ];
        } catch (ConnectException $e) {
            $message = $e->getCode() . chr(32) . $e->getMessage();

            $this->logger->debug(
                sprintf('Error while opening "%s" - ' . $message, $url),
                [
                    'crawlerId' => $crawlerId,
                ]
            );
            return [
                'errorlog' => [$message],
            ];
        }
    }

    protected function getResponse(string $url, array $options): ResponseInterface
    {
        $guzzleClientFactory = GeneralUtility::makeInstance(GuzzleClientFactory::class);
        return GeneralUtility::makeInstance(RequestFactory::class, $guzzleClientFactory)
            ->request($url, 'GET', $options);
    }

    /**
     * Builds HTTP request headers.
     */
    private function buildRequestHeaders(string $crawlerId): array
    {
        return [
            'Connection' => 'close',
            'X-T3Crawler' => $crawlerId,
            'User-Agent' => 'TYPO3 crawler',
        ];
    }
}
