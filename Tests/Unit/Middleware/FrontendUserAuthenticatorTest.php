<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Middleware;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Middleware\FrontendUserAuthenticator;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\FrontendUserAuthenticator::class)]
class FrontendUserAuthenticatorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    use ProphecyTrait;

    protected \TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $subject;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = md5('this_is_an_insecure_encryption_key');
        $this->subject = $this->createPartialMock(FrontendUserAuthenticator::class, []);
    }

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processRequestNotHandled(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-T3CRAWLER')->willReturn(null);

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        self::assertSame($handlerResponse, $response);
    }
}
