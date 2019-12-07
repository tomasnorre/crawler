<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

use AOE\Crawler\Event\EventDispatcher;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class EventDispatcherTest
 */
class EventDispatcherTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $oldObservers;

    /**
     * Used to save the old state of the registered observers for the dispatcher
     */
    protected function setUp(): void
    {
        $this->oldObservers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = [];
    }

    /**
     * Resets the hook configuration for the event dispatcher.
     */
    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = $this->oldObservers;
    }

    /**
     * This test should test if the dispatcher, dispatches events to the right observers
     * at the right time.
     *
     * @test
     * @dataProvider eventsAndResultsDataProvider
     */
    public function canDispatcherDispatchEvent($events, $expectedFooCalls, $expectedBarCalls): void
    {
        EventsHelper::$called_foo = 0;
        EventsHelper::$called_bar = 0;

        //we're bypassing the singleton here because we don't want to share data with former testcases. Therefore we mock the protected constructor and create a fresh dispatcher
        $dispatcher = $this->createPartialMock(EventDispatcher::class, ['__construct']);
        $observer = new EventsHelper();
        $observer->registerObservers($dispatcher);

        foreach ($events as $event) {
            $dispatcher->post($event['name'], $event['group'], $event['parameters']);
        }

        self::assertEquals(EventsHelper::$called_foo, $expectedFooCalls);
        self::assertEquals(EventsHelper::$called_bar, $expectedBarCalls);
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
                'expectedBarCalls' => 0,
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                ],
                'expectedFooCalls' => 0,
                'expectedBarCalls' => 1,
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                ],
                'expectedFooCalls' => 1,
                'expectedBarCalls' => 1,
            ],
            [
                'events' => [
                    ['name' => 'bar', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],
                    ['name' => 'foo', 'group' => 111, 'parameters' => ['foo', 'bar']],

                ],
                'expectedFooCalls' => 3,
                'expectedBarCalls' => 1,
            ],
        ];
    }
}
