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

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class EventDispatcherTest
 *
 * @package AOE\Crawler\Tests\Unit\Domain\Model
 */
class EventDispatcherTest extends UnitTestCase {
	
	
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
		
		$this->markTestSkipped('This is skipped atm as it fails with 7.6.x Travis Builds, please check issue on github, https://github.com/AOEpeople/crawler/issues/132');
		
		EventsHelper::$called_foo = 0;
		EventsHelper::$called_bar = 0;
		
		//we're bypassing the singleton here because we don't want to share data with former testcases. Therefore we mock the protected constructor and create a fresh dispatcher
		$dispatcher 	= $this->getMock('tx_crawler_domain_events_dispatcher',array('__construct'),array(),'',false);
		$observer 		= new EventsHelper();
		$observer->registerObservers($dispatcher);
		
		foreach($events as $event){
			$dispatcher->post($event['name'],$event['group'],$event['parameters']);
		}
		
		$this->assertEquals(EventsHelper::$called_foo,$expectedFooCalls);
		$this->assertEquals(EventsHelper::$called_bar,$expectedBarCalls);
	}
	
}