<?php

declare(strict_types=1);

namespace AOE\Crawler\Command;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Crawler;
use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 */
class ProcessQueueCommand extends Command
{
    private const CLI_STATUS_NOTHING_PROCCESSED = 0;
    private const CLI_STATUS_REMAIN = 1;
    private const CLI_STATUS_PROCESSED = 2;
    private const CLI_STATUS_ABORTED = 4;
    private string $processId;
    private array $extensionSettings;

    public function __construct(
        private readonly Crawler $crawler,
        private readonly CrawlerController $crawlerController,
        private readonly ProcessRepository $processRepository,
        private readonly QueueRepository $queueRepository,
    ) {
        $this->processId = md5(microtime() . random_bytes(12));
        parent::__construct();
    }

    /**
     * Crawler Command - Crawling the URLs from the queue
     *
     * Examples:
     *
     * --- Will trigger the crawler which starts to process the queue entries
     * $ typo3 crawler:crawlQueue
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $amount = $input->getOption('amount');
        $sleeptime = $input->getOption('sleeptime');
        $sleepafter = $input->getOption('sleepafter');

        $this->extensionSettings = $this->getExtensionSettings();

        $result = self::CLI_STATUS_NOTHING_PROCCESSED;

        if (!$this->crawler->isDisabled() && $this->checkAndAcquireNewProcess($this->processId)) {
            $countInARun = $amount ? (int) $amount : (int) $this->extensionSettings['countInARun'];
            $sleepAfterFinish = $sleepafter ? (int) $sleepafter : (int) $this->extensionSettings['sleepAfterFinish'];
            $sleepTime = $sleeptime ? (int) $sleeptime : (int) $this->extensionSettings['sleepTime'];

            try {
                // Run process:
                $result = $this->runProcess($countInARun, $sleepTime, $sleepAfterFinish);
            } catch (\Throwable $e) {
                $output->writeln('<warning>' . get_class($e) . ': ' . $e->getMessage() . '</warning>');
                $result = self::CLI_STATUS_ABORTED;
            }

            // Cleanup
            $this->processRepository->deleteProcessesWithoutItemsAssigned();
            $this->processRepository->markRequestedProcessesAsNotActive([$this->processId]);
            $this->queueRepository->unsetProcessScheduledAndProcessIdForQueueEntries([$this->processId]);

            $output->writeln(
                '<info>Unprocessed Items remaining:' . count(
                    $this->queueRepository->getUnprocessedItems()
                ) . ' (' . $this->processId . ')</info>'
            );
            $result |= (count(
                $this->queueRepository->getUnprocessedItems()
            ) > 0 ? self::CLI_STATUS_REMAIN : self::CLI_STATUS_NOTHING_PROCCESSED);
        } else {
            $result |= self::CLI_STATUS_ABORTED;
        }

        $output->writeln((string) $result);
        return $result & self::CLI_STATUS_ABORTED;
    }

    protected function configure(): void
    {
        $this->setDescription('Trigger the crawler to process the queue entries');

        $this->setHelp(
            'Crawler Command - Crawling the URLs from the queue' . chr(10) . chr(10) .
            '
            Examples:
              --- Will trigger the crawler which starts to process the queue entries
              $ typo3 crawler:processqueue --amount 15 --sleepafter 5 --sleeptime 2
            '
        );
        $this->addOption(
            'amount',
            '',
            InputOption::VALUE_OPTIONAL,
            'How many pages should be crawled during that run',
            '0'
        );

        $this->addOption(
            'sleepafter',
            '',
            InputOption::VALUE_OPTIONAL,
            'Amount of seconds which the system should use to relax after all crawls are done',
            '0'
        );

        $this->addOption(
            'sleeptime',
            '',
            InputOption::VALUE_OPTIONAL,
            'Amount of microseconds which the system should use to relax between crawls'
        );
    }

    /**
     * Running the functionality of the CLI (crawling URLs from queue)
     */
    private function runProcess(int $countInARun, int $sleepTime, int $sleepAfterFinish): int
    {
        $result = 0;
        $counter = 0;

        // Clean up the queue
        $this->queueRepository->cleanupQueue();

        // Select entries:
        $records = $this->queueRepository->fetchRecordsToBeCrawled($countInARun);

        if (!empty($records)) {
            $quidList = [];

            foreach ($records as $record) {
                $quidList[] = $record['qid'];
            }

            //save the number of assigned queue entries to determine how many have been processed later
            $numberOfAffectedRows = $this->queueRepository->updateProcessIdAndSchedulerForQueueIds(
                $quidList,
                $this->processId
            );
            $this->processRepository->updateProcessAssignItemsCount($numberOfAffectedRows, $this->processId);

            if ($numberOfAffectedRows !== count($quidList)) {
                return $result | self::CLI_STATUS_ABORTED;
            }

            foreach ($records as $record) {
                $result |= $this->crawlerController->readUrl($record['qid'], false, $this->processId);

                $counter++;
                // Just to relax the system
                usleep($sleepTime);

                // if during the start and the current read url the cli has been disable we need to return from the function
                // mark the process NOT as ended.
                if ($this->crawler->isDisabled()) {
                    return $result | self::CLI_STATUS_ABORTED;
                }

                if (!$this->processRepository->isProcessActive($this->processId)) {
                    $result |= self::CLI_STATUS_ABORTED;
                    //possible timeout
                    break;
                }
            }

            sleep($sleepAfterFinish);
        }

        if ($counter > 0) {
            $result |= self::CLI_STATUS_PROCESSED;
        }

        return $result;
    }

    /**
     * Try to acquire a new process with the given id
     * also performs some auto-cleanup for orphan processes
     */
    private function checkAndAcquireNewProcess(string $id): bool
    {
        $returnValue = true;

        $systemProcessId = getmypid();
        if (!$systemProcessId) {
            return false;
        }

        $processCount = 0;
        $orphanProcesses = [];

        $activeProcesses = $this->processRepository->findAllActive();

        /** @var Process $process */
        foreach ($activeProcesses as $process) {
            if ($process->getTtl() < time()) {
                $orphanProcesses[] = $process->getProcessId();
            } else {
                $processCount++;
            }
        }

        // if there are less than allowed active processes then add a new one
        if ($processCount < (int) $this->extensionSettings['processLimit']) {
            $this->processRepository->addProcess($id, $systemProcessId);
        } else {
            $returnValue = false;
        }

        $this->processRepository->deleteProcessesMarkedAsDeleted();
        $this->processRepository->markRequestedProcessesAsNotActive($orphanProcesses);
        $this->queueRepository->unsetProcessScheduledAndProcessIdForQueueEntries($orphanProcesses);

        return $returnValue;
    }

    private function getExtensionSettings(): array
    {
        return GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
    }
}
