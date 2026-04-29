<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Evaluates HTTP headers and checks if Crawler should register itself.
 * Needs to be run after TSFE initialization AND Frontend User Authentication.
 *
 * Once done, the queue is fetched, and then the frontend request runs through.
 *
 * Finally, at the very end, if the crawler is still running, output the data and replace the response.
 *
 * @internal since v12.0.0
 */
class CrawlerInitialization implements MiddlewareInterface
{
    protected Context $context;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    /**
     * @throws AspectNotFoundException
     * @throws ServiceUnavailableException
     */
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queueParameters = $request->getAttribute('tx_crawler');
        if ($queueParameters === null) {
            return $handler->handle($request);
        }

        $request = $request->withAttribute('tx_crawler', [
            'forceIndexing' => true,
            'running' => true,
            'parameters' => $queueParameters,
            'log' => ['User Groups: ' . ($queueParameters['feUserGroupList'] ?? '')],
        ]);

        // Execute the frontend request as is
        $response = $handler->handle($request);
        $noCache = !$request->getAttribute('frontend.cache.instruction')->isCachingAllowed();

        $crawlerData = $request->getAttribute('tx_crawler', []);
        $crawlerData['vars'] = [
            'id' => $GLOBALS['TSFE']->id,
            'gr_list' => implode(',', $this->context->getAspect('frontend.user')->getGroupIds()),
            'no_cache' => $noCache,
        ];

        // Send log data for crawler (serialized content)
        return $response->withHeader('X-T3Crawler-Meta', serialize($crawlerData));
    }
}
