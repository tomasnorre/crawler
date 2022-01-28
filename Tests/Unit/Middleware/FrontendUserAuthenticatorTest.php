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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;

/**
 * @covers \AOE\Crawler\Middleware\FrontendUserAuthenticator
 */
class FrontendUserAuthenticatorTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var FrontendUserAuthenticator
     */
    protected $subject;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = md5('this_is_an_insecure_encryption_key');
        $this->subject = self::getAccessibleMock(FrontendUserAuthenticator::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider isRequestHashMatchingQueueRecordDataProvider
     */
    public function isRequestHashMatchingQueueRecord($queueRecord, $hash, $expected): void
    {
        self::assertSame(
            $this->subject->_call('isRequestHashMatchingQueueRecord', $queueRecord, $hash),
            $expected
        );
    }

    public function isRequestHashMatchingQueueRecordDataProvider(): iterable
    {
        $queueRecord = [
            'qid' => '1234',
            'set_id' => '4321',
        ];

        // Faking the TYPO3 Encryption key as the DataProvider doesn't know about the TYPO3_CONF array
        $encryptionKey = md5('this_is_an_insecure_encryption_key');

        yield 'Hash match' => [
            'queueRecord' => $queueRecord,
            'hash' => md5($queueRecord['qid'] . '|' . $queueRecord['set_id'] . '|' . $encryptionKey),
            'expected' => true,
        ];
        yield 'Hash does not match' => [
            'queueRecord' => $queueRecord,
            'hash' => md5('qid' . '|' . 'set_id' . '|' . $encryptionKey),
            'expected' => false,
        ];
        yield 'queueRecord is not an array, there returning false' => [
            'queueRecord' => null,
            'hash' => '',
            'expected' => false,
        ];
    }
}
