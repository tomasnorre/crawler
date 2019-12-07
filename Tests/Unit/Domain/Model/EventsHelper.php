<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

use AOE\Crawler\Event\EventDispatcher;
use AOE\Crawler\Event\EventObserverInterface;

class EventsHelper implements EventObserverInterface
{
    public static $called_foo = 0;

    public static $called_bar = 0;

    public function fooFunc(): void
    {
        self::$called_foo++;
    }

    public function barFunc(): void
    {
        self::$called_bar++;
    }

    public function registerObservers(EventDispatcher $dispatcher): void
    {
        $dispatcher->addObserver($this, 'fooFunc', 'foo');
        $dispatcher->addObserver($this, 'barFunc', 'bar');
    }
}
