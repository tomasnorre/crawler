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
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\Handler\CrawlUrlHandler;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Value\QueueRow;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal since v12.0.0
 */
#[AsCommand(name: 'crawler:buildQueue', description: 'Create entries in the queue that can be processed at once',)]
class BuildQueueCommand extends Command
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly QueueRepository $queueRepository,
        private readonly PageRepository $pageRepository,
        private readonly PageTreeUrlCollector $pageTreeUrlCollector,
        private readonly QueueEntryWriter $queueEntryWriter,
        private readonly CrawlUrlHandler $crawlUrlHandler,
    ) {
        parent::__construct();
    }

    #[\Override]
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

        $this->addArgument('page', InputArgument::REQUIRED, 'The page from where the queue building should start');

        $this->addArgument('conf', InputArgument::REQUIRED, 'A comma separated list of crawler configurations');

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
            'Specifies output modes url : Will list URLs which wget could use as input. queue: Will put entries in the queue, to be processed by "typo3 messenger:consume crawler_high crawler crawler_low". exec: Will execute all entries right away!'
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
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getOption('mode') ?? 'queue';

        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();

        $pageId = MathUtility::forceIntegerInRange((int) $input->getArgument('page'), 0);
        if ($pageId === 0 || empty($this->pageRepository->getPage($pageId))) {
            $message = "Page {$pageId} is not a valid page, please check you root page id and try again.";
            MessageUtility::addErrorMessage($message);
            $output->writeln("<info>{$message}</info>");
            return Command::FAILURE;
        }

        $configurationKeys = $this->getConfigurationKeys((string) $input->getArgument('conf'));

        if ($mode === 'queue' || $mode === 'exec') {
            $reason = new Reason();
            $reason->setReason(Reason::REASON_CLI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            $this->eventDispatcher->dispatch(new InvokeQueueChangeEvent($reason));
        }

        if ($extensionSettings['cleanUpOldQueueEntries']) {
            $this->queueRepository->cleanUpOldQueueEntries();
        }

        $run = $this->createCrawlRun($input, $mode, $configurationKeys);
        $queueRows = $this->pageTreeUrlCollector->collect(
            $pageId,
            MathUtility::forceIntegerInRange((int) $input->getOption('depth'), 0, 99),
            $run
        );

        match ($mode) {
            'url' => $output->writeln('<info>' . implode(PHP_EOL, $run->getDownloadUrls()) . PHP_EOL . '</info>'),
            'exec' => $this->outputModeExec($output, $queueRows, $run),
            'queue' => $this->outputModeQueue($output, $queueRows, $run),
            default => $this->outputModeDefault($output, $queueRows, $run),
        };

        return Command::SUCCESS;
    }

    /**
     * Obtains configuration keys from the CLI arguments
     */
    private function getConfigurationKeys(string $conf): array
    {
        $parameter = trim($conf);
        return $parameter !== '' ? GeneralUtility::trimExplode(',', $parameter) : [];
    }

    private function outputUrls(array $queueRows, OutputInterface $output): void
    {
        /** @var QueueRow $row */
        foreach ($queueRows as $row) {
            if (empty($row->message)) {
                $output->writeln('<info>' . $row->urls . '</info>');
            } else {
                $output->writeln(
                    '<comment>Page "' . $row->pageTitle . '" is not added to queue' . $row->message . '</comment>'
                );
            }
        }
    }

    private function outputModeDefault(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $output->writeln(
            '<info>' . count(
                $run->getUrlList()
            ) . ' entries found for processing. (Use "mode" to decide action):</info>' . PHP_EOL
        );
        $this->outputUrls($queueRows, $output);
    }

    private function outputModeQueue(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $output->writeln('<info>Putting ' . count($run->getUrlList()) . ' entries in queue:</info>' . PHP_EOL);
        $this->outputUrls($queueRows, $output);
    }

    private function outputModeExec(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $progressBar = new ProgressBar($output);
        $output->writeln('<info>Executing ' . count($run->getUrlList()) . ' requests right away:</info>');
        $this->outputUrls($queueRows, $output);
        $output->writeln('<info>Processing</info>' . PHP_EOL);

        foreach ($progressBar->iterate($run->getQueueEntries()) as $queueRec) {
            $p = json_decode((string) $queueRec['parameters'], true);
            if (is_bool($p)) {
                continue;
            }

            $progressBar->clear();
            if (empty($p['procInstructions'][0])) {
                $procInstructionsString = '';
            } else {
                $procInstructionsString = ' (' . implode(',', $p['procInstructions']) . ')';
            }
            $output->writeln('<info>' . $p['url'] . $procInstructionsString . ' => ' . '</info>');
            $progressBar->display();

            // Bypass the message queue and crawl the entry right away
            $result = ($this->crawlUrlHandler)(new CrawlUrlMessage($this->queueRepository->add($queueRec)));

            $resultContent = $result['content'] ?? '';
            $requestResult = json_decode((string) $resultContent, true);

            $progressBar->clear();
            if (is_array($requestResult)) {
                $resLog = array_key_exists('log', $requestResult)
                && is_array($requestResult['log']) ? chr(9) . chr(9) .
                    implode(PHP_EOL . chr(9) . chr(9), $requestResult['log']) : '';
                $output->writeln('<info>OK: ' . $resLog . '</info>' . PHP_EOL);
            } else {
                $output->writeln(
                    '<error>Error checking Crawler Result:  ' . substr(
                        (string) preg_replace('/\s+/', ' ', strip_tags((string) $resultContent)),
                        0,
                        30000
                    ) . '...' . PHP_EOL . '</error>' . PHP_EOL
                );
            }
            $progressBar->display();
        }
        $output->writeln('');
    }

    private function createCrawlRun(InputInterface $input, mixed $mode, array $configurationKeys): CrawlRun
    {
        return new CrawlRun(
            $this->queueEntryWriter->getCurrentTime(),
            MathUtility::forceIntegerInRange((int) $input->getOption('number') ?: 30, 1, 1000),
            submitUrls: $mode === 'queue' || $mode === 'exec',
            collectDownloadUrls: $mode === 'url',
            // Entries of the exec mode are crawled right away, instead of being stored in the queue first
            collectQueueEntriesOnly: $mode === 'exec',
            configurationSelection: $configurationKeys,
        );
    }
}
