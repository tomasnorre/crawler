<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentFinisher implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $headerName = 'X-T3CRAWLER';

    /**
     * @var Context
     */
    protected $context;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $output = $handler->handle($request);

        $crawlerInformation = $request->getHeaderLine($this->headerName) ?? null;
        if (empty($crawlerInformation)) {
            return $output;
        }

        // Output log data for crawler (serialized content):
        $content = serialize($GLOBALS['TSFE']->applicationData['tx_crawler']);
        $response = new Response();
        $response->getBody()->write($content);

        return $response;
    }
}
