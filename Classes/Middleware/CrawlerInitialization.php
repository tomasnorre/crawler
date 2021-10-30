<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Evaluates HTTP headers and checks if Crawler should register itself.
 * Needs to be run after TSFE initialization AND Frontend User Authentication.
 *
 * Once done, the queue is fetched, and then the frontend request runs through.
 *
 * Finally, at the very end, if the crawler is still running, output the data and replace the response.
 */
class CrawlerInitialization implements MiddlewareInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    /**
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queueParameters = $request->getAttribute('tx_crawler');
        if ($queueParameters === null) {
            return $handler->handle($request);
        }

        $GLOBALS['TSFE']->applicationData['forceIndexing'] = true;
        $GLOBALS['TSFE']->applicationData['tx_crawler']['running'] = true;
        $GLOBALS['TSFE']->applicationData['tx_crawler']['parameters'] = $queueParameters;
        $GLOBALS['TSFE']->applicationData['tx_crawler']['log'] = [
            'User Groups: ' . $queueParameters['feUserGroupList'],
        ];

        $GLOBALS['TSFE']->applicationData['tx_crawler']['vars'] = [
            'id' => $GLOBALS['TSFE']->id,
            'gr_list' => implode(',', $this->context->getAspect('frontend.user')->getGroupIds()),
            'no_cache' => $GLOBALS['TSFE']->no_cache,
        ];

        $this->runPollSuccessHooks();

        return $handler->handle($request);
    }

    /**
     * Required because some extensions (staticpub) might never be requested to run due to some Core side effects
     * and since this is considered as error the crawler should handle it properly
     */
    protected function runPollSuccessHooks(): void
    {
        if (! is_array($GLOBALS['TSFE']->applicationData['tx_crawler']['content']['parameters']['procInstructions'])) {
            return;
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] ?? [] as $pollable) {
            if (in_array($pollable, $GLOBALS['TSFE']->applicationData['tx_crawler']['content']['parameters']['procInstructions'], true)) {
                if (empty($GLOBALS['TSFE']->applicationData['tx_crawler']['success'][$pollable])) {
                    $GLOBALS['TSFE']->applicationData['tx_crawler']['errorlog'][] = 'Error: Pollable extension (' . $pollable . ') did not complete successfully.';
                }
            }
        }
    }
}
