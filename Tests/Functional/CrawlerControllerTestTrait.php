<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Creates a CrawlerController with the real services of the functional test instance.
 */
trait CrawlerControllerTestTrait
{
    /**
     * @param list<non-empty-string>|null $mockedMethods
     */
    private function createCrawlerController(
        MessageBusInterface $messageBus,
        ?array $mockedMethods = null,
        ?QueueRepository $queueRepository = null,
    ): CrawlerController&AccessibleObjectInterface&MockObject {
        return $this->getAccessibleMock(CrawlerController::class, $mockedMethods, [
            $queueRepository ?? GeneralUtility::makeInstance(QueueRepository::class),
            GeneralUtility::makeInstance(ConfigurationRepository::class),
            GeneralUtility::makeInstance(PageRepository::class),
            GeneralUtility::makeInstance(EventDispatcher::class),
            GeneralUtility::makeInstance(IconFactory::class),
            GeneralUtility::makeInstance(ConfigurationService::class),
            GeneralUtility::makeInstance(UrlService::class),
            GeneralUtility::makeInstance(PageService::class),
            GeneralUtility::makeInstance(ProcessInstructionService::class),
            new CrawlUrlMessageDispatcher($messageBus),
            GeneralUtility::makeInstance(ExtensionConfigurationProvider::class),
        ]);
    }
}
