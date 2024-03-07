<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2023-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Service\BackendModuleLinkService;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Tests\Functional\LanguageServiceTestTrait;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleLinkServiceTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    use LanguageServiceTestTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];
    private BackendModuleLinkService $subject;
    private ModuleTemplate $moduleTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBackendRequest();
        $this->setupLanguageService();

        $mockedIcon = $this->createMock(Icon::class);
        $mockedIconFactory = $this->createMock(IconFactory::class);
        $mockedIconFactory->method('getIcon')->willReturn($mockedIcon);

        $this->subject = GeneralUtility::makeInstance(BackendModuleLinkService::class, $mockedIconFactory);

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('path', ['packageName' => 'tomasnorre/crawler']));

        $this->moduleTemplate = (GeneralUtility::makeInstance(ModuleTemplateFactory::class))->create($request);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRefreshLinkReturnLink(): void
    {
        $link = $this->subject->getRefreshLink($this->moduleTemplate, 1);

        self::assertIsString($link);
        self::assertStringContainsString('Refresh', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('SET%5B%27crawleraction%27%5D=crawleraction&amp;id=1', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAddLinkReturnsEmptyString(): void
    {
        self::assertEmpty($this->subject->getAddLink($this->moduleTemplate, 10, 20, false));

        self::assertEmpty($this->subject->getAddLink($this->moduleTemplate, 20, 10, true));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAddLinkReturnLink(): void
    {
        $link = $this->subject->getAddLink($this->moduleTemplate, 1, 10, true);

        self::assertIsString($link);
        self::assertStringContainsString('Add process', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('action=addProcess', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getModeLinkReturnEmptyString(): void
    {
        self::assertEmpty($this->subject->getModeLink($this->moduleTemplate, 'not-exting-mode'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getModeLinkReturnsDatailLink(): void
    {
        $link = $this->subject->getModeLink($this->moduleTemplate, 'detail');

        self::assertIsString($link);
        self::assertStringContainsString('Show only running processes', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('processListMode=simple', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getModeLinkReturnsSimpleLink(): void
    {
        $link = $this->subject->getModeLink($this->moduleTemplate, 'simple');

        self::assertIsString($link);
        self::assertStringContainsString('Show finished and terminated processes', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('processListMode=detail', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getEnableDisableLinkReturnsEnableLink(): void
    {
        $link = $this->subject->getEnableDisableLink($this->moduleTemplate, true);

        self::assertIsString($link);
        self::assertStringContainsString('Stop all processes and disable crawling', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('action=stopCrawling', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getEnableDisableLinkReturnsDisableLink(): void
    {
        $link = $this->subject->getEnableDisableLink($this->moduleTemplate, false);

        self::assertIsString($link);
        self::assertStringContainsString('Enable crawling', $link);
        self::assertStringContainsString('<a href="/typo3/module/page/crawler/process', $link);
        self::assertStringContainsString('action=resumeCrawling', $link);
        self::assertStringContainsString('class="btn btn-sm btn-default "', $link);
    }
}
