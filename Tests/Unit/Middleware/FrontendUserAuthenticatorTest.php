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
use Doctrine\DBAL\Result;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\FrontendUserAuthenticator::class)]
class FrontendUserAuthenticatorTest extends UnitTestCase
{
    protected \TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject $subject;
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = md5('this_is_an_insecure_encryption_key');
        $this->subject = self::getAccessibleMock(FrontendUserAuthenticator::class, null, [], '', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processRequestNotHandled(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getHeaderLine')->with('X-T3CRAWLER')->willReturn('');

        $handlerResponse = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with(self::identicalTo($request))->willReturn(
            $handlerResponse
        );

        $response = $this->subject->process($request, $handler);

        self::assertSame($handlerResponse, $response);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processAddsQueueParametersOfMatchingQueueRecordToRequest(): void
    {
        $context = $this->createMock(Context::class);
        $context->expects(self::never())->method('setAspect');
        $subject = new FrontendUserAuthenticator($this->createQueryBuilder('12', [
            'qid' => 12,
            'set_id' => 34,
            'parameters' => "{'url':'https://example.com/'}",
        ]), $context);
        $request = (new ServerRequest('https://example.com/'))
            ->withHeader('X-T3CRAWLER', '12:' . $this->getHash(12, 34));

        $handledRequest = null;
        $response = $subject->process($request, $this->createHandler($handledRequest));

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(ServerRequestInterface::class, $handledRequest);
        self::assertSame([
            'url' => 'https://example.com/',
        ], $handledRequest->getAttribute('tx_crawler'));
        self::assertSame($context, $subject->getContext());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processSetsFrontendUserGroupsOfQueueRecord(): void
    {
        $context = new Context();
        $subject = new FrontendUserAuthenticator($this->createQueryBuilder('12', [
            'qid' => 12,
            'set_id' => 34,
            'parameters' => '{"url":"https://example.com/","feUserGroupList":"1,2"}',
        ]), $context);
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $frontendUser->usergroup_column = 'usergroup';
        $frontendUser->userid_column = 'uid';
        $frontendUser->username_column = 'username';
        $request = (new ServerRequest('https://example.com/'))
            ->withHeader('X-T3CRAWLER', '12:' . $this->getHash(12, 34))
            ->withAttribute('frontend.user', $frontendUser);
        $frontendUser->expects(self::once())->method('fetchGroupData')->with(
            self::callback(
                static fn (ServerRequestInterface $request): bool => $request->getAttribute('tx_crawler') !== null
            )
        );

        $handledRequest = null;
        $subject->process($request, $this->createHandler($handledRequest));

        self::assertSame([
            'usergroup' => '0,-2,1,2',
            'uid' => PHP_INT_MAX,
            'username' => '',
        ], $frontendUser->user);
        $userAspect = $context->getAspect('frontend.user');
        self::assertInstanceOf(UserAspect::class, $userAspect);
        self::assertEquals([0, -2, 1, 2], $userAspect->get('groupIds'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('processRejectsRequestDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function processRejectsRequestWithoutMatchingQueueRecord(array|false $queueRecord, string $hash): void
    {
        $subject = new FrontendUserAuthenticator($this->createQueryBuilder('12', $queueRecord), new Context());
        $request = (new ServerRequest('https://example.com/'))->withHeader('X-T3CRAWLER', '12:' . $hash);
        $errorResponse = new Response('php://temp', 503);
        $errorController = $this->createMock(ErrorController::class);
        $errorController->expects(self::once())->method('unavailableAction')
            ->with($request, 'No crawler entry found')
            ->willReturn($errorResponse);
        GeneralUtility::addInstance(ErrorController::class, $errorController);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        self::assertSame($errorResponse, $subject->process($request, $handler));
    }

    public static function processRejectsRequestDataProvider(): iterable
    {
        $hash = md5('12|34|' . md5('this_is_an_insecure_encryption_key'));
        yield 'No queue record' => [false, $hash];
        yield 'Queue record without qid' => [[
            'set_id' => 34,
        ], $hash];
        yield 'Queue record without set id' => [[
            'qid' => 12,
        ], $hash];
        yield 'Hash of other set' => [[
            'qid' => 12,
            'set_id' => 35,
        ], $hash];
    }

    private function getHash(int $queueId, int $setId): string
    {
        return md5($queueId . '|' . $setId . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }

    private function createHandler(?ServerRequestInterface &$handledRequest): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request) use (&$handledRequest): ResponseInterface {
                $handledRequest = $request;
                return new Response();
            }
        );
        return $handler;
    }

    private function createQueryBuilder(string $queueId, array|false $queueRecord): QueryBuilder
    {
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects(self::once())->method('eq')->with('qid', ':qid')->willReturn('qid = :qid');
        $result = $this->createStub(Result::class);
        $result->method('fetchAssociative')->willReturn($queueRecord);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects(self::once())->method('createNamedParameter')->with($queueId)->willReturn(':qid');
        $queryBuilder->expects(self::once())->method('select')->with('*')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('from')->with('tx_crawler_queue')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('where')->with('qid = :qid')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);
        return $queryBuilder;
    }
}
