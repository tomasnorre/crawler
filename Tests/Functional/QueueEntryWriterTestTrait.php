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
use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the queue services with the real services of the functional test instance.
 */
trait QueueEntryWriterTestTrait
{
    /**
     * @param list<non-empty-string> $mockedMethods
     */
    private function createQueueEntryWriter(
        MessageBusInterface $messageBus,
        array $mockedMethods = [],
        ?QueueRepository $queueRepository = null,
    ): QueueEntryWriter&MockObject {
        return $this->getMockBuilder(QueueEntryWriter::class)
            ->setConstructorArgs([
                $queueRepository ?? GeneralUtility::makeInstance(QueueRepository::class),
                GeneralUtility::makeInstance(EventDispatcher::class),
                GeneralUtility::makeInstance(UrlService::class),
                GeneralUtility::makeInstance(ProcessInstructionService::class),
                new CrawlUrlMessageDispatcher($messageBus),
                GeneralUtility::makeInstance(ExtensionConfigurationProvider::class),
            ])
            ->onlyMethods($mockedMethods)
            ->getMock();
    }

    private function createPageConfigurationResolver(): PageConfigurationResolver
    {
        return new PageConfigurationResolver(
            GeneralUtility::makeInstance(PageService::class),
            GeneralUtility::makeInstance(ConfigurationService::class),
            GeneralUtility::makeInstance(ConfigurationRepository::class),
        );
    }
}
