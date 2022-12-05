<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal since v12.0.0
 */
interface BackendModuleControllerInterface
{
    public function handleRequest(ServerRequestInterface $request): ResponseInterface;
}
