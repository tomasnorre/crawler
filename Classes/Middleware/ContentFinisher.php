<?php

declare(strict_types=1);

namespace AOE\Crawler\Middleware;


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
