<?php
class tx_crawler_domain_events_test implements tx_crawler_domain_events_observer{
	
	public static $called_foo = 0;
	
	public static $called_bar = 0;

	/**
	 * @return void
	 */
	public function fooFunc(){
		self::$called_foo++;
	}
	
	/**
	 * @return void
	 */
	public function barFunc(){
		self::$called_bar++;
	}
	
	/**
	 * @param $dispatcher
	 */
	public function registerObservers(tx_crawler_domain_events_dispatcher $dispatcher) {
		$dispatcher->addObserver($this,'fooFunc','foo');
		$dispatcher->addObserver($this,'barFunc','bar');
	}
}
?>