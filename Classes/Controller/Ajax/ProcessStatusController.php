<?php

declare(strict_types=1);

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Controller\Ajax;

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;

class ProcessStatusController
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
    ) {
    }

    public function getProcessStatus(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        $id = $data['id'] ?? null;

        $response = new Response();

        if ($id === null) {
            return $response->withStatus(400, 'No process ID provided');
        }

        $process = $this->processRepository->findByProcessId($id);
        if ($process === null) {
            return $response->withStatus(404, 'Process with ID ' . $id . ' not found');
        }

        $content = json_encode(
            [
                'status' => $process->getProgress(),
                'procesedItems' => $process->getAmountOfItemsProcessed(),
                'runtime' => $process->getRuntime(),
                'processId' => $id,
            ]
        );
        if ($content === false) {
            throw new \RuntimeException('Failed to encode JSON response', 1760971184);
        }
        $response->getBody()->write($content);
        return $response;
    }
}
