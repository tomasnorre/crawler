<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Middleware;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Middleware\CrawlerInitialization;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\CrawlerInitialization::class)]
class CrawlerInitializationTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    use ProphecyTrait;

    #[\PHPUnit\Framework\Attributes\Test]
    public function processRequestNotHandled(): void
    {
        $subject = self::getAccessibleMock(CrawlerInitialization::class, [], [], '', false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('tx_crawler')->willReturn(null);

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $subject->process($request->reveal(), $handler->reveal());

        self::assertSame($handlerResponse, $response);
    }
}
