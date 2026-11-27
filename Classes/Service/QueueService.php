<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\QueueEntryWriter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class QueueService
{
    /**
     * The requests per minute only schedule the URL list of the crawl run, which isn't used here
     */
    private const REQUESTS_PER_MINUTE = 300;

    /**
     * The set id of all queue entries added by this service
     */
    private readonly int $setId;

    public function __construct(
        private readonly QueueEntryWriter $queueEntryWriter,
        private readonly PageConfigurationResolver $pageConfigurationResolver,
    ) {
        $this->setId = GeneralUtility::md5int(microtime());
    }

    public function addPageToQueue(int $pageUid, int $time = 0): void
    {
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage($pageUid, true);
        $configurations = $this->pageConfigurationResolver->getConfigurationsForPageRow($pageData);
        // Currently this is only used from the DataHandlerHook, and we don't know of any allowed/disallowed configurations,
        // when clearing the cache, therefore we allow all configurations in this case.
        // This next lines could be skipped as it will return the incoming configurations, but for visibility and
        // later implementation it's kept as it do no harm.
        $allowedConfigurations = [];
        $configurations = ConfigurationService::removeDisallowedConfigurations($allowedConfigurations, $configurations);
        // No processing instructions are selected, so the URLs of all configurations are added,
        // each with the processing instructions of its configuration
        $run = new CrawlRun($time, self::REQUESTS_PER_MINUTE, submitUrls: true, setId: $this->setId);

        foreach ($configurations as $configuration) {
            $this->queueEntryWriter->compileUrlList($configuration, $pageData, $run);
        }
    }
}
