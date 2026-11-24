<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend\Interface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal since v12.0.0
 *
 * @psalm-api handleRequest() is called by the backend routing, see Configuration/Backend/Modules.php
 */
interface BackendModuleControllerInterface
{
    public function handleRequest(ServerRequestInterface $request): ResponseInterface;
}
