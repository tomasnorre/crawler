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
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Value\QueueRow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class BuildQueueCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Create entries in the queue that can be processed at once');

        $this->setHelp(
            'Try "typo3 help crawler:buildQueue" to see your options' . chr(10) . chr(10) .
            'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
It can put entries in the queue from command line options, return the list of URLs and even execute
all entries right away without having to queue them up - this can be useful for immediate re-cache,
re-indexing or static publishing from command line.' . chr(10) . chr(10) .
            '
            Examples:
              --- Re-cache pages from page 7 and two levels down, executed immediately
              $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 2 --mode exec

              --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
              $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 0 --mode queue --number 4
            '
        );

        $this->addArgument(
            'page',
            InputArgument::REQUIRED,
            'The page from where the queue building should start'
        );

        $this->addArgument(
            'conf',
            InputArgument::REQUIRED,
            'A comma separated list of crawler configurations'
        );

        $this->addOption(
            'depth',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Tree depth, 0-99\', "How many levels under the \'page_id\' to include.',
            '0'
        );

        $this->addOption(
            'mode',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Specifies output modes url : Will list URLs which wget could use as input. queue: Will put entries in queue table. exec: Will execute all entries right away!'
        );

        $this->addOption(
            'number',
            '',
            InputOption::VALUE_OPTIONAL,
            'Specifies how many items are put in the queue per minute. Only valid for output mode "queue"',
            '0'
        );
    }

    /**
     * Crawler Command - Submitting URLs to be crawled.
     *
     * Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
     * It can put entries in the queue from command line options, return the list of URLs and even execute
     * all entries right away without having to queue them up - this can be useful for immediate re-cache,
     * re-indexing or static publishing from command line.
     *
     * Examples:
     *
     * --- Re-cache pages from page 7 and two levels down, executed immediately
     * $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 2 --mode exec
     *
     *
     * --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
     * $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 0 --mode queue --number 4
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var JsonCompatibilityConverter $jsonCompatibilityConverter */
        $jsonCompatibilityConverter = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
        $mode = $input->getOption('mode') ?? 'queue';

        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var CrawlerController $crawlerController */
        $crawlerController = $objectManager->get(CrawlerController::class);
        /** @var QueueRepository $queueRepository */
        $queueRepository = $objectManager->get(QueueRepository::class);

        if ($mode === 'exec') {
            $crawlerController->registerQueueEntriesInternallyOnly = true;
        }

        $pageId = MathUtility::forceIntegerInRange((int) $input->getArgument('page'), 0);
        if ($pageId === 0) {
            $message = "Page {$pageId} is not a valid page, please check you root page id and try again.";
            MessageUtility::addErrorMessage($message);
            $output->writeln("<info>{$message}</info>");
            return 1;
        }

        $configurationKeys = $this->getConfigurationKeys((string) $input->getArgument('conf'));

        if ($mode === 'queue' || $mode === 'exec') {
            $reason = new Reason();
            $reason->setReason(Reason::REASON_CLI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            $eventDispatcher->dispatch(new InvokeQueueChangeEvent($reason));
        }

        if ($extensionSettings['cleanUpOldQueueEntries']) {
            $queueRepository->cleanUpOldQueueEntries();
        }

        $crawlerController->setID = GeneralUtility::md5int(microtime());
        $queueRows = $crawlerController->getPageTreeAndUrls(
            $pageId,
            MathUtility::forceIntegerInRange((int) $input->getOption('depth'), 0, 99),
            $crawlerController->getCurrentTime(),
            MathUtility::forceIntegerInRange((int) $input->getOption('number') ?: 30, 1, 1000),
            $mode === 'queue' || $mode === 'exec',
            $mode === 'url',
            [],
            $configurationKeys
        );

        if ($mode === 'url') {
            $output->writeln('<info>' . implode(PHP_EOL, $crawlerController->downloadUrls) . PHP_EOL . '</info>');
        } elseif ($mode === 'exec') {
            $progressBar = new ProgressBar($output);
            $output->writeln('<info>Executing ' . count($crawlerController->urlList) . ' requests right away:</info>');
            $this->outputUrls($queueRows, $output);
            $output->writeln('<info>Processing</info>' . PHP_EOL);

            foreach ($progressBar->iterate($crawlerController->queueEntries) as $queueRec) {
                $p = $jsonCompatibilityConverter->convert($queueRec['parameters']);
                if (is_bool($p)) {
                    continue;
                }

                $progressBar->clear();
                $output->writeln('<info>' . $p['url'] . ' (' . implode(',', $p['procInstructions']) . ') => ' . '</info>' . PHP_EOL);
                $progressBar->display();

                $result = $crawlerController->readUrlFromArray($queueRec);

                $resultContent = $result['content'] ?? '';
                $requestResult = $jsonCompatibilityConverter->convert($resultContent);

                $progressBar->clear();
                if (is_array($requestResult)) {
                    $resLog = array_key_exists('log', $requestResult) && is_array($requestResult['log']) ? PHP_EOL . chr(9) . chr(9) . implode(PHP_EOL . chr(9) . chr(9), $requestResult['log']) : '';
                    $output->writeln('<info>OK: ' . $resLog . '</info>' . PHP_EOL);
                } else {
                    $output->writeln('<error>Error checking Crawler Result:  ' . substr(preg_replace('/\s+/', ' ', strip_tags($resultContent)), 0, 30000) . '...' . PHP_EOL . '</error>' . PHP_EOL);
                }
                $progressBar->display();
            }
            $output->writeln('');
        } elseif ($mode === 'queue') {
            $output->writeln('<info>Putting ' . count($crawlerController->urlList) . ' entries in queue:</info>' . PHP_EOL);
            $this->outputUrls($queueRows, $output);
        } else {
            $output->writeln('<info>' . count($crawlerController->urlList) . ' entries found for processing. (Use "mode" to decide action):</info>' . PHP_EOL);
            $this->outputUrls($queueRows, $output);
        }

        return 0;
    }

    /**
     * Obtains configuration keys from the CLI arguments
     */
    private function getConfigurationKeys(string $conf): array
    {
        $parameter = trim($conf);
        return ($parameter !== '' ? GeneralUtility::trimExplode(',', $parameter) : []);
    }

    private function outputUrls(array $queueRows, OutputInterface $output): void
    {
        /** @var QueueRow $row */
        foreach ($queueRows as $row) {
            if (empty($row->message)) {
                $output->writeln('<info>' . $row->urls . '</info>');
            } else {
                $output->writeln('<warning>' . $row->pageTitle . ': ' . $row->message . '</warning>');
            }
        }
    }
}
