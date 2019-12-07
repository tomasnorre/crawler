<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Event\EventDispatcher;
use AOE\Crawler\Event\EventObserverInterface;

/**
 * Class EventsHelper
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class EventsHelper implements EventObserverInterface
{
    public static $called_foo = 0;
    
    public static $called_bar = 0;
    
    /**
     * @return void
     */
    public function fooFunc(): void
    {
        self::$called_foo++;
    }
    
    /**
     * @return void
     */
    public function barFunc(): void
    {
        self::$called_bar++;
    }
    
    /**
     * @param EventDispatcher $dispatcher
     *
     * @return void
     */
    public function registerObservers(EventDispatcher $dispatcher): void
    {
        $dispatcher->addObserver($this, 'fooFunc', 'foo');
        $dispatcher->addObserver($this, 'barFunc', 'bar');
    }
}
