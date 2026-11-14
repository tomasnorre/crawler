<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\Queue\QueueCallbackInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CallbackExecutionStrategy::class)]
class CallbackExecutionStrategyTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function executesCallbackFromTheContainer(): void
    {
        $callback = $this->createMock(QueueCallbackInterface::class);
        $callback->expects(self::once())
            ->method('execute')
            ->with([
                'name' => 'Crawler',
            ])
            ->willReturn('From the container');

        $callbacks = $this->createStub(ContainerInterface::class);
        $callbacks->method('has')->willReturn(true);
        $callbacks->method('get')->willReturn($callback);

        self::assertSame(
            'From the container',
            (new CallbackExecutionStrategy($callbacks))->fetchByCallback('Vendor\\Callback', [
                'name' => 'Crawler',
            ])
        );
    }

    #[Test]
    public function createsCallbackThatIsNotInTheContainer(): void
    {
        self::assertSame(
            'Hi, it works! Crawler',
            (new CallbackExecutionStrategy($this->createEmptyContainer()))->fetchByCallback(
                CallbackObjectForTesting::class,
                [
                    'name' => 'Crawler',
                ]
            )
        );
    }

    #[Test]
    public function throwsExceptionForCallbackWithoutInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1763020800);

        (new CallbackExecutionStrategy($this->createEmptyContainer()))->fetchByCallback(\stdClass::class, []);
    }

    #[Test]
    public function throwsExceptionForUnknownCallbackClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1763020800);

        (new CallbackExecutionStrategy($this->createEmptyContainer()))->fetchByCallback('Vendor\\DoesNotExist', []);
    }

    private function createEmptyContainer(): ContainerInterface
    {
        $callbacks = $this->createStub(ContainerInterface::class);
        $callbacks->method('has')->willReturn(false);
        return $callbacks;
    }
}
