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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessStatusController
{
    public function getProcessStatus(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        $id = $data['id'] ?? null;

        $response = new Response();

        if ($id === null) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No process ID provided',
            ]));
            return $response;
        }

        $process = $this->getProcess($id);

        $response->getBody()->write(json_encode(
            [
                'status' => $process->getProgress(),
                'procesedItems' => $process->getAmountOfItemsProcessed(),
                'processId' => $id,
            ]
        ));
        return $response;
    }

    private function getProcess(string $id): ?Process
    {
        $processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
        return $processRepository->findByProcessId($id);
    }
}
