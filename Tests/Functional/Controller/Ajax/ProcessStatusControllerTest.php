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
use AOE\Crawler\Domain\Repository\ProcessRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(ProcessStatusController::class)]
class ProcessStatusControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private ProcessStatusController $subject;

    protected function setUp(): void
    {
        $processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
        $this->subject = new ProcessStatusController($processRepository);

        parent::setUp();
    }

    #[Test]
    public function getProcessStatusReturn400BadRequestWhenNoBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn(json_encode([]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')
            ->willReturn($stream);
        $response = $this->subject->getProcessStatus( $request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('No process ID provided', $response->getReasonPhrase());
    }

    #[Test]
    public function getProcessStatusReturn404NotFoundWhenNotFound()
    {
        $processId = 'abcdef';
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn(json_encode(['id' => $processId]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')
            ->willReturn($stream);

        $response = $this->subject->getProcessStatus($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Process with ID: ' . $processId .' not found', $response->getReasonPhrase());
    }

    #[Test]
    public function getProcessStatusReturnJson()
    {
        $this->markTestSkipped('Please implement');
    }
}
