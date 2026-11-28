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
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Value\CrawlResult;
use AOE\Crawler\Value\QueueRow;
use Psr\Clock\ClockInterface;
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
 *
 * @psalm-api Console command, registered in Configuration/Services.yaml
 */
#[AsCommand(name: 'crawler:buildQueue', description: 'Create entries in the queue that can be processed at once',)]
class BuildQueueCommand extends Command
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly QueueRepository $queueRepository,
        private readonly PageRepository $pageRepository,
        private readonly PageTreeUrlCollector $pageTreeUrlCollector,
        private readonly CrawlUrlHandler $crawlUrlHandler,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
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
     * --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
     * $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 0 --mode queue --number 4
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $typedInput = new TypedInput($input);
        $mode = $typedInput->getStringOption('mode', 'queue');

        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionSettings();

        $pageId = MathUtility::forceIntegerInRange($typedInput->getIntArgument('page'), 0);
        if ($pageId === 0 || empty($this->pageRepository->getPage($pageId))) {
            $message = "Page {$pageId} is not a valid page, please check you root page id and try again.";
            MessageUtility::addErrorMessage($message);
            $output->writeln("<info>{$message}</info>");
            return Command::FAILURE;
        }

        $configurationKeys = $this->getConfigurationKeys($typedInput->getStringArgument('conf'));

        if ($mode === 'queue' || $mode === 'exec') {
            $reason = new Reason();
            $reason->setReason(Reason::REASON_CLI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            $this->eventDispatcher->dispatch(new InvokeQueueChangeEvent($reason));
        }

        if ($extensionSettings->cleanUpOldQueueEntries) {
            $this->queueRepository->cleanUpOldQueueEntries();
        }

        $run = $this->createCrawlRun($typedInput, $mode, $configurationKeys);
        $queueRows = $this->pageTreeUrlCollector->collect(
            $pageId,
            MathUtility::forceIntegerInRange($typedInput->getIntOption('depth'), 0, 99),
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
     *
     * @return list<string>
     */
    private function getConfigurationKeys(string $conf): array
    {
        $parameter = trim($conf);
        return $parameter !== '' ? GeneralUtility::trimExplode(',', $parameter) : [];
    }

    /**
     * @param list<QueueRow> $queueRows
     */
    private function outputUrls(array $queueRows, OutputInterface $output): void
    {
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

    /**
     * @param list<QueueRow> $queueRows
     */
    private function outputModeDefault(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $output->writeln(
            '<info>' . count(
                $run->getUrlList()
            ) . ' entries found for processing. (Use "mode" to decide action):</info>' . PHP_EOL
        );
        $this->outputUrls($queueRows, $output);
    }

    /**
     * @param list<QueueRow> $queueRows
     */
    private function outputModeQueue(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $output->writeln('<info>Putting ' . count($run->getUrlList()) . ' entries in queue:</info>' . PHP_EOL);
        $this->outputUrls($queueRows, $output);
    }

    /**
     * @param list<QueueRow> $queueRows
     */
    private function outputModeExec(OutputInterface $output, array $queueRows, CrawlRun $run): void
    {
        $progressBar = new ProgressBar($output);
        $output->writeln('<info>Executing ' . count($run->getUrlList()) . ' requests right away:</info>');
        $this->outputUrls($queueRows, $output);
        $output->writeln('<info>Processing</info>' . PHP_EOL);

        foreach ($progressBar->iterate($run->getQueueEntries()) as $queueRec) {
            $this->executeQueueEntry($output, $progressBar, $queueRec);
        }
        $output->writeln('');
    }

    /**
     * @param array<string, mixed> $queueRec
     */
    private function executeQueueEntry(OutputInterface $output, ProgressBar $progressBar, array $queueRec): void
    {
        $p = json_decode((string) $queueRec['parameters'], true);
        if (is_bool($p)) {
            return;
        }

        $progressBar->clear();
        $output->writeln('<info>' . $p['url'] . $this->getProcInstructionsString($p) . ' => </info>');
        $progressBar->display();

        // Bypass the message queue and crawl the entry right away
        $result = ($this->crawlUrlHandler)(new CrawlUrlMessage($this->queueRepository->add($queueRec)));

        $progressBar->clear();
        $this->outputCrawlResult($output, $result);
        $progressBar->display();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getProcInstructionsString(array $parameters): string
    {
        if (empty($parameters['procInstructions'][0])) {
            return '';
        }
        return ' (' . implode(',', $parameters['procInstructions']) . ')';
    }

    private function outputCrawlResult(OutputInterface $output, ?CrawlResult $result): void
    {
        if (!$result?->hasCrawlerData()) {
            $content = $result?->getContent();
            $content = is_string($content) ? $content : json_encode($content, JSON_THROW_ON_ERROR);
            $output->writeln(
                '<error>Error checking Crawler Result:  ' . substr(
                    (string) preg_replace('/\s+/', ' ', strip_tags($content)),
                    0,
                    30000
                ) . '...' . PHP_EOL . '</error>' . PHP_EOL
            );
            return;
        }

        $log = $result->getLog();
        $resLog = $log === [] ? '' : chr(9) . chr(9) . implode(PHP_EOL . chr(9) . chr(9), $log);
        $output->writeln('<info>OK: ' . $resLog . '</info>' . PHP_EOL);
    }

    /**
     * @param list<string> $configurationKeys
     */
    private function createCrawlRun(TypedInput $input, string $mode, array $configurationKeys): CrawlRun
    {
        return new CrawlRun(
            $this->clock->now()->getTimestamp(),
            MathUtility::forceIntegerInRange($input->getIntOption('number') ?: 30, 1, 1000),
            submitUrls: $mode === 'queue' || $mode === 'exec',
            collectDownloadUrls: $mode === 'url',
            // Entries of the exec mode are crawled right away, instead of being stored in the queue first
            collectQueueEntriesOnly: $mode === 'exec',
            configurationSelection: $configurationKeys,
        );
    }
}
