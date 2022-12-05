<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface BackendModuleControllerInterface
{
    public function handleRequest(ServerRequestInterface $request): ResponseInterface;
}
