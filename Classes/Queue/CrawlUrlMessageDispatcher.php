<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue;

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

use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Dispatches crawl messages to the message bus.
 *
 * As long as the CrawlUrlMessage is routed to the "crawler" transport (the default, see ext_localconf.php),
 * the message is sent to the transport matching its priority. The priority is taken from the
 * sitemap_priority of the page (EXT:seo), if not given explicitly.
 * If the routing has been changed, e.g. to the synchronous "default" transport, the routing is respected.
 *
 * @internal
 */
class CrawlUrlMessageDispatcher
{
    public const DEFAULT_TRANSPORT = 'crawler';

    /**
     * @var array<int, CrawlPriority>
     */
    private array $pagePriorities = [];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(
        CrawlUrlMessage $message,
        int $pageId = 0,
        int $delayInSeconds = 0,
        ?CrawlPriority $priority = null
    ): void {
        $stamps = [];
        if ($delayInSeconds > 0) {
            $stamps[] = new DelayStamp($delayInSeconds * 1000);
        }
        if ($this->isRoutedToDefaultTransport()) {
            $priority ??= $this->getPriorityForPage($pageId);
            $stamps[] = new TransportNamesStamp([$priority->getTransportName()]);
        }

        $this->messageBus->dispatch($message, $stamps);
    }

    private function getPriorityForPage(int $pageId): CrawlPriority
    {
        if ($pageId <= 0 || !ExtensionManagementUtility::isLoaded('seo')) {
            return CrawlPriority::Normal;
        }

        if (!isset($this->pagePriorities[$pageId])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $sitemapPriority = $queryBuilder
                ->select('sitemap_priority')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();
            $this->pagePriorities[$pageId] = $sitemapPriority === false
                ? CrawlPriority::Normal
                : CrawlPriority::fromSitemapPriority((float) $sitemapPriority);
        }

        return $this->pagePriorities[$pageId];
    }

    private function isRoutedToDefaultTransport(): bool
    {
        $routing = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class] ?? null;
        return (array) $routing === [self::DEFAULT_TRANSPORT];
    }
}
