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
        $run = new CrawlRun(
            $time,
            300,
            submitUrls: true,
            processingInstructions: array_keys($this->getCrawlerProcInstructions()),
            setId: $this->setId,
        );

        foreach ($configurations as $configuration) {
            $this->queueEntryWriter->compileUrlList($configuration, $pageData, $run);
        }
    }

    /**
     * Reads the registered processingInstructions of the crawler
     */
    private function getCrawlerProcInstructions(): array
    {
        $crawlerProcInstructions = [];
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $configuration) {
                $crawlerProcInstructions[$configuration['key']] = $configuration['value'];
            }
        }

        return $crawlerProcInstructions;
    }
}
