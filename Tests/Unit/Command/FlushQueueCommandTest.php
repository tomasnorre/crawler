<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Command;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Value\QueueFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(FlushQueueCommand::class)]
final class FlushQueueCommandTest extends UnitTestCase
{
    #[Test]
    public function commandIsConfigured(): void
    {
        $command = new FlushQueueCommand();

        self::assertSame('crawler:flushQueue', $command->getName());
        self::assertSame('Remove queue entries and perform a cleanup', $command->getDescription());
        self::assertSame(
            'Try "typo3 help crawler:flushQueue" to see your options' . "\n\n"
            . 'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;' . "\n"
            . 'It will remove queue entries and perform a cleanup.' . "\n\n"
            . "\n"
            . '            Examples:' . "\n"
            . '              --- Remove all finished queue-entries' . "\n"
            . '              $ typo3 crawler:flushQueue finished' . "\n"
            . "\n"
            . '              --- Remove all pending queue-entries' . "\n"
            . '              $ typo3 crawler:flushQueue pending' . "\n"
            . '            ',
            $command->getHelp()
        );

        $definition = $command->getDefinition();
        self::assertSame(1, $definition->getArgumentCount());
        self::assertTrue($definition->getArgument('mode')->isRequired());
        self::assertSame('What to clear: all, finished, pending', $definition->getArgument('mode')->getDescription());
    }

    #[Test]
    #[DataProvider('flushQueueDataProvider')]
    public function executeFlushesTheQueueEntriesOfTheMode(string $mode, string $expectedOutput): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())
            ->method('flushQueue')
            ->with(new QueueFilter($mode));
        GeneralUtility::addInstance(QueueRepository::class, $queueRepository);

        $commandTester = new CommandTester(new FlushQueueCommand());
        $status = $commandTester->execute([
            'mode' => $mode,
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame($expectedOutput . PHP_EOL, $commandTester->getDisplay());
    }

    public static function flushQueueDataProvider(): iterable
    {
        yield 'all' => ['all', 'All entries in Crawler queue have been flushed'];
        yield 'finished' => ['finished', 'All entries in Crawler queue with status "finished" have been flushed'];
        yield 'pending' => ['pending', 'All entries in Crawler queue with status "pending" have been flushed'];
    }

    #[Test]
    public function executeThrowsExceptionForInvalidMode(): void
    {
        // The mode is validated before the queue repository is used
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1794663700);

        (new CommandTester(new FlushQueueCommand()))->execute([
            'mode' => 'invalid',
        ]);
    }

    #[Test]
    public function executeRequiresTheModeArgument(): void
    {
        $this->expectException(RuntimeException::class);

        (new CommandTester(new FlushQueueCommand()))->execute([]);
    }
}
