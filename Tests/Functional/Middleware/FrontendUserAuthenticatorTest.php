<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Middleware;

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

use AOE\Crawler\Middleware\FrontendUserAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(FrontendUserAuthenticator::class)]
class FrontendUserAuthenticatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private FrontendUserAuthenticator $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(FrontendUserAuthenticator::class);
    }

    #[Test]
    public function processQueueEntryNotFound(): never
    {
        $this->markTestSkipped('WIP');
        $this->setGlobalsSys();
        $request = (new ServerRequest('https://crawler-devbox.ddev.site/'))
            ->withHeader('X-T3CRAWLER', '404:entry-not-found');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        $response = $this->subject->process($request, $handler);

        self::assertStringContainsString('No crawler entry found', $response->getBody()->getContents());
    }

    #[DataProvider('processSetsExpectedUserGroupsDataProvider')]
    #[Test]
    public function processSetsExpectedUserGroups(string $feGroups, string $headerLine): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProcessHandlesFeGroups/tx_crawler_queue.csv');

        $queueParametersArray = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $request = (new ServerRequest('https://crawler-devbox.ddev.site/'))
            ->withHeader('X-T3CRAWLER', $headerLine)
            ->withAttribute('frontend.user', $this->createStub(FrontendUserAuthentication::class));

        $handlerResponse = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with(
            self::callback(
                static fn (ServerRequestInterface $handledRequest): bool => $handledRequest->getAttribute(
                    'tx_crawler'
                ) === $queueParametersArray
            )
        )->willReturn($handlerResponse);

        $response = $this->subject->process($request, $handler);

        $feGroupsArray = explode(',', $feGroups);

        foreach ($feGroupsArray as $feGroup) {
            self::assertContains(
                $feGroup,
                $this->subject->getContext()->getAspect('frontend.user')->get('groupIds')
            );
        }

        self::assertEquals(200, $response->getStatusCode());
    }

    public static function processSetsExpectedUserGroupsDataProvider(): iterable
    {
        yield 'One FE Group' => [
            'feGroups' => '1',
            'headerLine' => '1006:28f6fd71036abbe3452a0bf9ca10ee38',
        ];

        yield 'Two FE Groups' => [
            'feGroups' => '1,2',
            'headerLine' => '1007:8e6edae3da393a9412898ef59e6cf925',
        ];
    }

    private function setGlobalsSys(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS'] = [
            'caching' => [
                'cacheConfigurations' => [
                    'hash' => [
                        'backend' => NullBackend::class,
                    ],
                    'imagesizes' => [
                        'backend' => NullBackend::class,
                    ],
                    'pages' => [
                        'backend' => NullBackend::class,
                    ],
                    'pagesection' => [
                        'backend' => NullBackend::class,
                    ],
                    'rootline' => [
                        'backend' => NullBackend::class,
                    ],
                ],
            ],
            'devIPmask' => '',
            'displayErrors' => 0,
            'encryptionKey' => '3a5826140e97e15e5f2f6de051e7e0f903958cb8d9a9caadaf6b237be88f53bde31462ef070939161e30e8ac101e2f3f',
            'exceptionalErrors' => 4096,
            'features' => [
                'unifiedPageTranslationHandling' => true,
            ],
            'sitename' => 'Crawler Devbox',
            'systemMaintainers' => [2, 5, 5, 1, 1],
            'trustedHostsPattern' => '.*',
        ];
    }
}
