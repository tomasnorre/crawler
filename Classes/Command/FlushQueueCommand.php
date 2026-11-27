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

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Value\QueueFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 *
 * @psalm-api Console command, registered in Configuration/Services.yaml
 */
#[AsCommand(name: 'crawler:flushQueue', description: 'Remove queue entries and perform a cleanup',)]
class FlushQueueCommand extends Command
{
    #[\Override]
    protected function configure(): void
    {
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
        $this->addArgument('mode', InputArgument::REQUIRED, 'What to clear: all, finished, pending');
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
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueFilter = new QueueFilter((new TypedInput($input))->getStringArgument('mode'));

        $queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        $queueRepository->flushQueue($queueFilter);
        $output->writeln(match ((string) $queueFilter) {
            'all' => '<info>All entries in Crawler queue have been flushed</info>',
            default => '<info>All entries in Crawler queue with status "' . $queueFilter . '" have been flushed</info>',
        });

        return Command::SUCCESS;
    }
}
