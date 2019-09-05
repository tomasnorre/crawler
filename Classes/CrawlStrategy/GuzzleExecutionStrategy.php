<?php
declare(strict_types=1);
namespace AOE\Crawler\CrawlStrategy;

/*
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

use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls Guzzle / CURL (based on TYPO3 settings) for fetching a URL.
 */
class GuzzleExecutionStrategy implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Sets up a CURL / Guzzle Request for fetching the request.
     *
     * @param UriInterface $url
     * @param string $crawlerId
     * @return bool|mixed
     */
    public function fetchUrlContents(UriInterface $url, string $crawlerId)
    {
        $reqHeaders = $this->buildRequestHeaders($crawlerId);

        $options = ['headers' => $reqHeaders];
        if ($url->getUserInfo()) {
            $options['auth'] = explode(':', $url->getUserInfo());
        }
        try {
            $url = (string)$url;
            $response = GeneralUtility::makeInstance(RequestFactory::class)
                ->request(
                    $url,
                    'GET',
                    $options
                );
            $contents = $response->getBody()->getContents();
            return unserialize($contents);
        } catch (ServerException $e) {
            $this->logger->debug(
                sprintf('Error while opening "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
            return false;
        }
    }

    /**
     * Builds HTTP request headers.
     *
     * @param string $crawlerId
     * @return array
     */
    protected function buildRequestHeaders(string $crawlerId): array
    {
        return [
            'Connection' => 'close',
            'X-T3Crawler' => $crawlerId,
            'User-Agent' => 'TYPO3 crawler'
        ];
    }

}
