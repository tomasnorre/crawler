<?php
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
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class EventDispatcherTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class EventDispatcherTest extends UnitTestCase
{

    /**
     * @var array
     */
    protected $oldObservers;

    /**
     * Used to save the old state of the registered observers for the dispatcher
     *
     * @return void
     */
    public function setUp()
    {
        $this->oldObservers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = [];
    }

    /**
     * Resets the hook configuration for the event dispatcher.
     *
     * @return void
     */
    public function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = $this->oldObservers;
    }

    /**
     * This test should test if the dispatcher, dispatches events to the right observers
     * at the right time.
     *
     * @test
     * @dataProvider eventsAndResultsDataProvider
     *
     */
    public function canDispatcherDispatchEvent($events, $expectedFooCalls, $expectedBarCalls)
    {
        EventsHelper::$called_foo = 0;
        EventsHelper::$called_bar = 0;

        //we're bypassing the singleton here because we don't want to share data with former testcases. Therefore we mock the protected constructor and create a fresh dispatcher
        $dispatcher = $this->createMock(EventDispatcher::class, ['__construct'], [], '', false);
        $observer = new EventsHelper();
        $observer->registerObservers($dispatcher);

        foreach ($events as $event) {
            $dispatcher->post($event['name'], $event['group'], $event['parameters']);
        }

        $this->assertEquals(EventsHelper::$called_foo, $expectedFooCalls);
        $this->assertEquals(EventsHelper::$called_bar, $expectedBarCalls);
    }

    /**
     * Holds testdata for events and expected calls of the observers
     *
     * @return array
     */
    public function eventsAndResultsDataProvider()
    {
        return [
            [
                'events' => [
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                ],
                'expectedFooCalls' => 1,
                'expectedBarCalls' => 0
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                ],
                'expectedFooCalls' => 0,
                'expectedBarCalls' => 1
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                ],
                'expectedFooCalls' => 1,
                'expectedBarCalls' => 1
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],

                ],
                'expectedFooCalls' => 3,
                'expectedBarCalls' => 1
            ],
        ];
    }
}
