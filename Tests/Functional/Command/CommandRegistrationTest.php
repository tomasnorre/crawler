<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Command;

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

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Command\FlushQueueCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Checks that the crawler commands are registered once, as commands and not as aliases,
 * so `typo3 list` shows them.
 */
final class CommandRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['info'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    #[Test]
    #[DataProvider('commandDataProvider')]
    public function commandIsListed(string $commandName, string $className): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        $commands = $commandRegistry->filter('crawler');

        self::assertArrayHasKey($commandName, $commands);
        self::assertSame($commandName, $commands[$commandName]['name']);
        self::assertSame($className, $commands[$commandName]['serviceName']);
        self::assertSame([], $commands[$commandName]['aliases']);
        self::assertContains($commandName, $commandRegistry->getNamespaces()['crawler']['commands'] ?? []);
    }

    #[Test]
    #[DataProvider('commandDataProvider')]
    public function commandCanBeCreated(string $commandName, string $className): void
    {
        $command = $this->get(CommandRegistry::class)->get($commandName);

        self::assertInstanceOf($className, $command);
        self::assertSame($commandName, $command->getName());
        self::assertSame([], $command->getAliases());
        self::assertFalse($command->isHidden());
    }

    public static function commandDataProvider(): iterable
    {
        yield 'crawler:buildQueue' => [
            'commandName' => 'crawler:buildQueue',
            'className' => BuildQueueCommand::class,
        ];
        yield 'crawler:flushQueue' => [
            'commandName' => 'crawler:flushQueue',
            'className' => FlushQueueCommand::class,
        ];
    }
}
