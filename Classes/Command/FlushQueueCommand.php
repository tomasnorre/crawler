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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class FlushQueueCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp(
            'Try "typo3 help crawler:flushQueue" to see your options' . chr(10) . chr(10) .
            'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
It will remove queue entries and perform a cleanup.' . chr(10) . chr(10) .
            '
            Examples:
              --- Remove all finished queue-entries in the sub-branch of page 5
              $ typo3 crawler:flushQueue --mode finished --page 5
             
              --- Remove all pending queue-entries for all pages
              $ typo3 crawler:flushQueue --mode pending
            '
        );
        $this->addOption(
            'mode',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Output mode',
            'finished'
        );

        $this->addOption(
            'page',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Page to start',
            0
        );
    }

    /**
     * Crawler Command - Cleaning up the queue.
     *
     * Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
     * It will remove queue entries and perform a cleanup.
     *
     * Examples:
     *
     * --- Remove all finished queue-entries in the sub-branch of page 5
     * $ typo3 crawler:flushQueue --mode finished --page 5
     *
     * --- Remove all pending queue-entries for all pages
     * $ typo3 crawler:flushQueue --mode pending
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var CrawlerController $crawlerController */
        $crawlerController = $objectManager->get(CrawlerController::class);

        $pageId = MathUtility::forceIntegerInRange($input->getOption('page'), 0);
        $fullFlush = ($pageId === 0);

        switch ($input->getOption('mode')) {
            case 'all':
                $crawlerController->getLogEntriesForPageId($pageId, '', true, $fullFlush);
                break;
            case 'finished':
            case 'pending':
                $crawlerController->getLogEntriesForPageId($pageId, $input->getOption('mode'), true, $fullFlush);
                break;
            default:
                $output->writeln('<info>No matching parameters found.' . PHP_EOL . 'Try "typo3 help crawler:flushQueue" to see your options</info>');
                break;
        }
    }
}
