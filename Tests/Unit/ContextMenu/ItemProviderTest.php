<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\ContextMenu;

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

use AOE\Crawler\ContextMenu\ItemProvider;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Class ItemProviderTest
 * @covers \AOE\Crawler\ContextMenu\ItemProvider
 */
class ItemProviderTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication|null
     */
    private $oldBackendUser;

    protected function setUp(): void
    {
        $mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->expects($this->any())->method('sL')->willReturn('language string');
        $GLOBALS['LANG'] = $mockedLanguageService;
        $this->oldBackendUser = $GLOBALS['BE_USER'] ?? null;
        $backendUserStub = $this->createStub(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserStub;
    }

    protected function tearDown(): void
    {
        if ($this->oldBackendUser) {
            $GLOBALS['BE_USER'] = $this->oldBackendUser;
        } else {
            unset($GLOBALS['BE_USER']);
        }
    }

    /**
     * @test
     */
    public function canHandleTxCrawlerConfigurationTable(): void
    {
        $subject = new ItemProvider();
        $subject->setContext('tx_crawler_configuration', 'identifier');
        self::assertTrue($subject->canHandle());
    }

    /**
     * @test
     */
    public function cannotHandleTxCrawlerQueueTable(): void
    {
        $subject = new ItemProvider('tx_crawler_queue', 'identifier');
        self::assertFalse($subject->canHandle());
    }

    /**
     * @test
     */
    public function getPriorityReturnsExpectedValue(): void
    {
        $subject = new ItemProvider('tx_crawler_configuration', 'identifier');
        self::assertEquals(50, $subject->getPriority());
    }
}
