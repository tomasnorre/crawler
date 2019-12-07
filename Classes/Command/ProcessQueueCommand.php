<?php

declare(strict_types=1);

namespace AOE\Crawler\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ProcessQueueCommand extends Command
{
    public const CLI_STATUS_NOTHING_PROCCESSED = 0;

    public const CLI_STATUS_REMAIN = 1; //queue not empty

    public const CLI_STATUS_PROCESSED = 2; //(some) queue items where processed

    public const CLI_STATUS_ABORTED = 4; //instance didn't finish

    public const CLI_STATUS_POLLABLE_PROCESSED = 8;

    /**
     * Crawler Command - Crawling the URLs from the queue
     *
     * Examples:
     *
     * --- Will trigger the crawler which starts to process the queue entries
     * $ typo3 crawler:crawlQueue
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $amount = $input->getOption('amount');
        $sleeptime = $input->getOption('sleeptime');
        $sleepafter = $input->getOption('sleepafter');

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $result = self::CLI_STATUS_NOTHING_PROCCESSED;

        /** @var CrawlerController $crawlerController */
        $crawlerController = $objectManager->get(CrawlerController::class);
        /** @var QueueRepository $queueRepository */
        $queueRepository = $objectManager->get(QueueRepository::class);

        if (! $crawlerController->getDisabled() && $crawlerController->CLI_checkAndAcquireNewProcess($crawlerController->CLI_buildProcessId())) {
            $countInARun = $amount ? intval($amount) : $crawlerController->extensionSettings['countInARun'];
            $sleepAfterFinish = $sleeptime ? intval($sleeptime) : $crawlerController->extensionSettings['sleepAfterFinish'];
            $sleepTime = $sleepafter ? intval($sleepafter) : $crawlerController->extensionSettings['sleepTime'];

            try {
                // Run process:
                $result = $crawlerController->CLI_run($countInARun, $sleepTime, $sleepAfterFinish);
            } catch (\Throwable $e) {
                $output->writeln('<warning>' . get_class($e) . ': ' . $e->getMessage() . '</warning>');
                $result = self::CLI_STATUS_ABORTED;
            }

            // Cleanup
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_crawler_process');
            $queryBuilder
                ->delete('tx_crawler_process')
                ->where(
                    $queryBuilder->expr()->eq('assigned_items_count', 0)
                )
                ->execute();

            $crawlerController->CLI_releaseProcesses($crawlerController->CLI_buildProcessId());

            $output->writeln('<info>Unprocessed Items remaining:' . $queueRepository->countUnprocessedItems() . ' (' . $crawlerController->CLI_buildProcessId() . ')</info>');
            $result |= ($queueRepository->countUnprocessedItems() > 0 ? self::CLI_STATUS_REMAIN : self::CLI_STATUS_NOTHING_PROCCESSED);
        } else {
            $result |= self::CLI_STATUS_ABORTED;
        }

        return $output->writeln($result);
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Crawler Command - Crawling the URLs from the queue' . chr(10) . chr(10) .
            '
            Examples:
              --- Will trigger the crawler which starts to process the queue entries
              $ typo3 crawler:crawlQueue
            '
        );
        $this->addOption(
            'amount',
            '',
            InputOption::VALUE_OPTIONAL,
            'How many pages should be crawled during that run',
            0
        );

        $this->addOption(
            'sleepafter',
            '',
            InputOption::VALUE_OPTIONAL,
            'Amount of milliseconds which the system should use to relax between crawls',
            0
        );

        $this->addOption(
            'sleeptime',
            '',
            InputOption::VALUE_OPTIONAL,
            'Amount of seconds which the system should use to relax after all crawls are done.'
        );
    }
}
