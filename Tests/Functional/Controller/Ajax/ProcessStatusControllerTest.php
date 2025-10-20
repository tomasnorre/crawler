<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Controller\Ajax;

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

use AOE\Crawler\Controller\Ajax\ProcessStatusController;
use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ProcessStatusControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private ProcessStatusController $controller;

    protected function setUp(): void
    {
        $processRepository = $this->createMock(ProcessRepository::class);
        $processRepository->expects($this->any())->method('findByProcessId')->willReturn(null);

        $processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
        $this->controller = new ProcessStatusController($processRepository);
        parent::setUp();
    }

    #[Test]
    public function getProcessStatusReturnsBadRequestAsNoIdIsGiven()
    {
        $serverRequest = new ServerRequest('https://example.com/typo3/');
        $serverRequest = $serverRequest->withParsedBody(['id' => null]);

        $response = $this->controller->getProcessStatus($serverRequest);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('No process ID provided', $response->getReasonPhrase());
    }

    #[Test]
    public function getProcessStatusReturns404WhenProcessIsNotFound()
    {
        $processId = 'abcdefg';
        $serverRequest = new ServerRequest('https://example.com/typo3/');
        $serverRequest = $serverRequest->withMethod('POST');
        $serverRequest = $serverRequest->withParsedBody(['id' => $processId]);

        $response = $this->controller->getProcessStatus($serverRequest);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Process with ID ' . $processId . ' not found', $response->getReasonPhrase());
    }
}
