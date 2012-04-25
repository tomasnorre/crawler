<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/events/interface.tx_crawler_domain_events_observer.php';
require_once t3lib_extMgm::extPath('crawler') . 'tests/events/data/class.tx_crawler_domain_events_test.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/events/class.tx_crawler_domain_events_dispatcher.php';


class tx_crawler_domain_events_dispatcher_testcase extends tx_phpunit_testcase {

	protected $oldObservers;

	/**
	 * Used to save the old state of the registered observers for the dispatcher
	 *
	 * @return void
	 */
	public function setUp(){
		$this->oldObservers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'];
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = array();
	}

	/**
	 * Resets the hook configuration for the event dispatcher.
	 *
	 * @return void
	 */
	public function tearDown(){
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] = $this->oldObservers;
	}


	/**
	 * Holds testdata for events and expected calls of the observers
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return array
	 */
	public function eventsAndResults(){
		return array(
				array(
					'events' => array(
						array('name' => 'foo', 'group' => 111, 'parameters' =>array('foo','bar')),
					),
					'expectedFooCalls' => 1,
					'expectedBarCalls' => 0
				),
				array(
					'events' => array(
						array('name' => 'bar', 'group' => 111, 'parameters' =>array('foo','bar')),
					),
					'expectedFooCalls' => 0,
					'expectedBarCalls' => 1
				),
				array(
					'events' => array(
						array('name' => 'bar', 'group' => 111, 'parameters' =>array('foo','bar')),
						array('name' => 'foo', 'group' => 111, 'parameters' =>array('foo','bar')),
					),
					'expectedFooCalls' => 1,
					'expectedBarCalls' => 1
				),
				array(
					'events' => array(
						array('name' => 'bar', 'group' => 111, 'parameters' =>array('foo','bar')),
						array('name' => 'foo', 'group' => 111, 'parameters' =>array('foo','bar')),
						array('name' => 'foo', 'group' => 111, 'parameters' =>array('foo','bar')),
						array('name' => 'foo', 'group' => 111, 'parameters' =>array('foo','bar')),

					),
					'expectedFooCalls' => 3,
					'expectedBarCalls' => 1
				),
		);
	}

	/**
	 * This test should test if the dispatcher, dispatches events to the right observers
	 * at the right time.
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @dataProvider eventsAndResults
	 * @test
	 */
	public function canDispatcherDispatchEvent($events,$expectedFooCalls,$expectedBarCalls){
		tx_crawler_domain_events_test::$called_foo = 0;
		tx_crawler_domain_events_test::$called_bar = 0;
		
			//we're bypassing the signleton here because we don't want to share data with former testcases. Therefore we mock the protected constructor and create a fresh dispatcher
		$dispatcher 	= $this->getMock('tx_crawler_domain_events_dispatcher',array('__construct'),array(),'',false);
		$observer 		= new tx_crawler_domain_events_test();
		$observer->registerObservers($dispatcher);
		
		foreach($events as $event){
			$dispatcher->post($event['name'],$event['group'],$event['parameters']);
		}

		$this->assertEquals(tx_crawler_domain_events_test::$called_foo,$expectedFooCalls);
		$this->assertEquals(tx_crawler_domain_events_test::$called_bar,$expectedBarCalls);
	}
}
?>