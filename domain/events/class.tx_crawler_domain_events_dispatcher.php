<?php
/**
 * The event dispatcher can be used to register an observer for a
 * given event. The observer needs to implement the inferface
 * tx_crawler_domain_events_observer
 * 
 * each observer needs to be registered as a TYPO3 Hook. 
 * Example:
 * 
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'][] = 'EXT:aoe_xyz/domain/events/class.tx_xyz_domain_events_crawler.php:tx_xyz_domain_events_crawler';
 * 
 * in the registerObservers the observer can register itself for events:
 * 
 * 	public function registerObservers(tx_crawler_domain_events_dispatcher $dispatcher){		
 *		$dispatcher->addObserver($this,'addUrl','urlAddedToQueue');
 *		$dispatcher->addObserver($this,'duplicateUrlInQueue','duplicateUrlInQueue');
 * 		$dispatcher->addObserver($this,'urlCrawled','urlCrawled');
 *		$dispatcher->addObserver($this,'invokeQueueChange','invokeQueueChange');
 * 		$dispatcher->addObserver($this,'contentChange','contentChange');
 * 		$dispatcher->addObserver($this,'workspaceChange','workspaceChange');
 *	}
 * 
 * The dispatcher is a singleton. The instance can be retrieved by:
 * 
 * tx_crawler_domain_events_dispatcher::getInstance();
 * 
 * Events can be posted by tx_crawler_domain_events_dispatcher::getInstance()->post('myEvent','eventGroup', array('foo' => 'bar'));
 * 
 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
 */
class tx_crawler_domain_events_dispatcher{
	
	/* @var array $observers array with registered observers;*/
	protected $observers;
	
	protected static $instance;
	
	/**
	 * The __constructor is private because the dispatcher is a singleton
	 * 
	 * @param void
	 * @return void
	 */
    private function __construct() {
    	$this->observers = array();
    } 
	
    /**
     * Returns all registered eventtypes.
     * 
     * @return array array with registered events.
     */
	protected function getEvents(){
		return array_keys($this->observers);		
	}
	
	/**
	 * This method can be used to add an observer for an event to the dispatcher
	 * 
	 * @param $observer_object
	 * @param $observer_method
	 * @param $event
	 * 
	 * @return void
	 */
	public function addObserver($observer_object, $observer_method, $event){
		$this->observers[$event][] = array('object' => $observer_object, 'method' => $observer_method);
	}

	/**
	 * Enables checking whether a certain event is observed by anyone
	 * 
	 * @param $event
	 * @return boolean
	 */
	public function hasObserver($event) {
		return count($this->observers[$event]) > 0;
	}

	/**
	 * This method should be used to post a event to the dispatcher. Each 
	 * registered observer will be notified about the event.
	 * 
	 * @param $event
	 * @param $group
	 * @param $attachedData
	 * 
	 * @return void
	 */
	public function post($event, $group, $attachedData){
		if(is_array($this->observers[$event])){
			foreach($this->observers[$event] as $eventObserver){
				call_user_func(array($eventObserver['object'],$eventObserver['method']),$event,$group,$attachedData);
			}
		}
	}
	
	/**
	 * Returns the instance of the dispatcher singleton
	 * 
	 * @return tx_crawler_domain_events_dispatcher
	 */
	public static function getInstance(){
		global $TYPO3_CONF_VARS;
		if(!self::$instance instanceof tx_crawler_domain_events_dispatcher){
			$dispatcher = new tx_crawler_domain_events_dispatcher();
					
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'])) {
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] as $classRef) {
					$hookObj = &t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'registerObservers')) {
						$hookObj->registerObservers($dispatcher);
					}
				}
			}
			
			self::$instance = $dispatcher;
		}
		
		return self::$instance;
	}
}
?>