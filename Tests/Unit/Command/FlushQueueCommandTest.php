<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Command;

use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Controller\CrawlerController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FlushQueueCommandTest extends TestCase
{
    /**
     * @var CrawlerController|MockObject
     */
    private $crawlerControllerMock;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp()
    {
        $this->crawlerControllerMock = $this->createMock(CrawlerController::class);

        $command = new FlushQueueCommand($this->crawlerControllerMock);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForModeAll
     */
    public function commandIsCalledWithModeAll(string $mode, string $page = ''): void
    {
        $this->crawlerControllerMock
            ->expects(self::once())
            ->method('getLogEntriesForPageId')
            ->with((int)$page, '', true, true);

        $arguments = ['mode' => $mode];
        if ($page) {
            $arguments['--page'] = $page;
        }
        $this->commandTester->execute($arguments);

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertSame('All entries in Crawler queue will be flushed', trim($this->commandTester->getDisplay()));
    }

    public function dataProviderForModeAll(): \Generator
    {
        yield 'mode and no page' => [
            'all',
        ];

        yield 'mode in different cases and no page' => [
            'AlL',
        ];

        yield 'mode and page' => [
            'all',
            '42',
        ];
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForModePendingAndFinished
     */
    public function commandIsCalledWithModePendingOrFinished(string $mode, string $page = ''): void
    {
        $this->crawlerControllerMock
            ->expects(self::once())
            ->method('getLogEntriesForPageId')
            ->with((int)$page, strtolower($mode), true, false);

        $arguments = ['mode' => $mode];
        if ($page) {
            $arguments['--page'] = $page;
        }
        $this->commandTester->execute($arguments);

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertSame(
            sprintf('All entries in Crawler queue, with status: "%s" will be flushed', strtolower($mode)),
            trim($this->commandTester->getDisplay())
        );
    }

    public function dataProviderForModePendingAndFinished(): \Generator
    {
        yield 'mode pending and no page' => [
            'pending',
        ];

        yield 'mode pending in different cases and no page' => [
            'pEnDINg',
        ];

        yield 'mode pending and page' => [
            'pending',
            '12'
        ];

        yield 'mode finished and no page' => [
            'finished',
        ];

        yield 'mode finished in different cases and no page' => [
            'FInIShED',
        ];

        yield 'mode finished and page' => [
            'finished',
            '13'
        ];
    }

    /**
     * @test
     */
    public function commandIsCalledWithNegativePageThenPageIsSetTo0(): void
    {
        $this->crawlerControllerMock
            ->expects(self::once())
            ->method('getLogEntriesForPageId')
            ->with(0, '', true, true);

        $this->commandTester->execute(['mode' => 'all', '--page' => '-123']);

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertSame('All entries in Crawler queue will be flushed', trim($this->commandTester->getDisplay()));
    }

    /**
     * @test
     */
    public function commandIsCalledWithInvalidMode(): void
    {
        $this->crawlerControllerMock
            ->expects(self::never())
            ->method('getLogEntriesForPageId');

        $this->commandTester->execute(['mode' => 'invalid']);

        self::assertSame(0, $this->commandTester->getStatusCode());
        self::assertStringStartsWith('No matching parameters found.', trim($this->commandTester->getDisplay()));
    }
}
