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
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Event\EventDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class BuildQueueCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp(
            'Try "typo3 help crawler:flushQueue" to see your options' . chr(10) . chr(10) .
            'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module; 
It can put entries in the queue from command line options, return the list of URLs and even execute
all entries right away without having to queue them up - this can be useful for immediate re-cache,
re-indexing or static publishing from command line.' . chr(10) . chr(10) .
            '
            Examples:
              --- Re-cache pages from page 7 and two levels down, executed immediately
              $ typo3 crawler:buildQueue --page 7 --depth 2 --conf defaultConfiguration --mode exec
             
              --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
              $ typo3 crawler:buildQueue --page 7 --depth 0 --conf defaultConfiguration --mode queue --number 4
            '
        );

        $this->addOption(
            'conf',
            'c',
            InputOption::VALUE_REQUIRED,
            'A comma separated list of crawler configurations'
        );

        $this->addOption(
            'page',
            'p',
            InputOption::VALUE_OPTIONAL,
            'The page from where the queue building should start',
            0
        );

        $this->addOption(
            'depth',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Tree depth, 0-99\', "How many levels under the \'page_id\' to include.',
            0
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
            0
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
     * $ typo3 crawler:buildQueue --page 7 --depth 2 --conf defaultConfiguration --mode exec
     *
     *
     * --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
     * $ typo3 crawler:buildQueue --page 7 --depth 0 --conf defaultConfiguration --mode queue --number 4
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $mode = $input->getOption('mode');

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var CrawlerController $crawlerController */
        $crawlerController = $objectManager->get(CrawlerController::class);

        if ($mode === 'exec') {
            $crawlerController->registerQueueEntriesInternallyOnly = true;
        }

        $pageId = MathUtility::forceIntegerInRange($input->getOption('page'), 0);

        $configurationKeys = $this->getConfigurationKeys($input->getOption('conf'));

        if (! is_array($configurationKeys)) {
            $configurations = $crawlerController->getUrlsForPageId($pageId);
            if (is_array($configurations)) {
                $configurationKeys = array_keys($configurations);
            } else {
                $configurationKeys = [];
            }
        }

        if ($mode === 'queue' || $mode === 'exec') {
            $reason = new Reason();
            $reason->setReason(Reason::REASON_GUI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            EventDispatcher::getInstance()->post(
                'invokeQueueChange',
                $crawlerController->setID,
                ['reason' => $reason]
            );
        }

        if ($crawlerController->extensionSettings['cleanUpOldQueueEntries']) {
            $crawlerController->cleanUpOldQueueEntries();
        }

        $crawlerController->setID = (int) GeneralUtility::md5int(microtime());
        $crawlerController->getPageTreeAndUrls(
            $pageId,
            MathUtility::forceIntegerInRange($input->getOption('depth'), 0, 99),
            $crawlerController->getCurrentTime(),
            MathUtility::forceIntegerInRange($input->getOption('number') ?: 30, 1, 1000),
            $mode === 'queue' || $mode === 'exec',
            $mode === 'url',
            [],
            $configurationKeys
        );

        if ($mode === 'url') {
            $output->writeln('<info>' . implode(PHP_EOL, $crawlerController->downloadUrls) . PHP_EOL . '</info>');
        } elseif ($mode === 'exec') {
            $output->writeln('<info>Executing ' . count($crawlerController->urlList) . ' requests right away:</info>');
            $output->writeln('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
            $output->writeln('<info>Processing</info>' . PHP_EOL);

            foreach ($crawlerController->queueEntries as $queueRec) {
                $p = unserialize($queueRec['parameters']);
                $output->writeln('<info>' . $p['url'] . ' (' . implode(',', $p['procInstructions']) . ') => ' . '</info>' . PHP_EOL);
                $result = $crawlerController->readUrlFromArray($queueRec);

                $requestResult = unserialize($result['content']);
                if (is_array($requestResult)) {
                    $resLog = is_array($requestResult['log']) ? PHP_EOL . chr(9) . chr(9) . implode(PHP_EOL . chr(9) . chr(9), $requestResult['log']) : '';
                    $output->writeln('<info>OK: ' . $resLog . '</info>' . PHP_EOL);
                } else {
                    $output->writeln('<errror>Error checking Crawler Result:  ' . substr(preg_replace('/\s+/', ' ', strip_tags($result['content'])), 0, 30000) . '...' . PHP_EOL . '</errror>' . PHP_EOL);
                }
            }
        } elseif ($mode === 'queue') {
            $output->writeln('<info>Putting ' . count($crawlerController->urlList) . ' entries in queue:</info>' . PHP_EOL);
            $output->writeln('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
        } else {
            $output->writeln('<info>' . count($crawlerController->urlList) . ' entries found for processing. (Use "mode" to decide action):</info>' . PHP_EOL);
            $output->writeln('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
        }
    }

    /**
     * Obtains configuration keys from the CLI arguments
     *
     * @param $conf string
     * @return array
     */
    private function getConfigurationKeys($conf)
    {
        $parameter = trim($conf);
        return ($parameter !== '' ? GeneralUtility::trimExplode(',', $parameter) : []);
    }
}
