<?php

namespace AOE\Crawler\ContextMenu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Model\CrawlerQueueItem;
use AOE\Crawler\Domain\Repository\CrawlerQueueItemRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ContextMenuActionController
 *
 * @package Crawler
 */
class ContextMenuActionController
{

    /**
     * @var CrawlerQueueItemRepository
     */
    protected $crawlerQueueItemRepository;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * ContextMenuActionController constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->crawlerQueueItemRepository = $this->objectManager->get(CrawlerQueueItemRepository::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function addPageToQueue(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParameters = $request->getQueryParams();

        /** @var CrawlerQueueItem $crawlerQueueItem */
        $crawlerQueueItem = $this->objectManager->get(CrawlerQueueItem::class);
        $crawlerQueueItem->setPageUid($queryParameters['uid']);
        $this->crawlerQueueItemRepository->add($crawlerQueueItem);

        $this->persistenceManager->persistAll();

        return $response;
    }
}
