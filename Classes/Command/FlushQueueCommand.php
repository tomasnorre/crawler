<?php

declare(strict_types=1);

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

namespace AOE\Crawler\Command;

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Value\QueueFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class FlushQueueCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Remove queue entries and perform a cleanup');

        $this->setHelp(
            'Try "typo3 help crawler:flushQueue" to see your options' . chr(10) . chr(10) .
            'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
It will remove queue entries and perform a cleanup.' . chr(10) . chr(10) .
            '
            Examples:
              --- Remove all finished queue-entries
              $ typo3 crawler:flushQueue finished

              --- Remove all pending queue-entries
              $ typo3 crawler:flushQueue pending
            '
        );
        $this->addArgument(
            'mode',
            InputArgument::REQUIRED,
            'What to clear: all, finished, pending'
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
     * --- Remove all finished queue-entries
     * $ typo3 crawler:flushQueue finished
     *
     * --- Remove all pending queue-entries for all pages
     * $ typo3 crawler:flushQueue pending
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $queueFilter = new QueueFilter($input->getArgument('mode'));

        /** @var QueueRepository $queueRepository */
        $queueRepository = $objectManager->get(QueueRepository::class);

        switch ($queueFilter) {
            case 'all':
                $queueRepository->flushQueue($queueFilter);
                $output->writeln('<info>All entries in Crawler queue will be flushed</info>');
                break;
            case 'finished':
            case 'pending':
                $queueRepository->flushQueue($queueFilter);
                $output->writeln('<info>All entries in Crawler queue, with status: "' . $queueFilter . '" will be flushed</info>');
                break;
            default:
                $output->writeln('<info>No matching parameters found.' . PHP_EOL . 'Try "typo3 help crawler:flushQueue" to see your options</info>');
                break;
        }

        return 0;
    }
}
