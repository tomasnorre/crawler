<?php
interface tx_crawler_domain_events_observer{
	
	/**
	 * This method should be implemented by the observer to register events 
	 * that should be forwarded to the observer
	 * 
	 * @param tx_crawler_domain_events_dispatcher $dispatcher
	 * @return boolean
	 */
	public function registerObservers(tx_crawler_domain_events_dispatcher $dispatcher);
}

?>