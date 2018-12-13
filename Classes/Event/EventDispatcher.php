<?php
namespace AOE\Crawler\Event;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The event dispatcher can be used to register an observer for a
 * given event. The observer needs to implement the inferface
 * EventObserverInterface
 *
 * each observer needs to be registered as a TYPO3 Hook.
 * Example:
 *
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'][] = 'EXT:aoe_xyz/domain/events/class.tx_xyz_domain_events_crawler.php:tx_xyz_domain_events_crawler';
 *
 * in the registerObservers the observer can register itself for events:
 *
 * 	public function registerObservers(EventDispatcher $dispatcher) {
 *		$dispatcher->addObserver($this,'addUrl','urlAddedToQueue');
 *		$dispatcher->addObserver($this,'duplicateUrlInQueue','duplicateUrlInQueue');
 * 		$dispatcher->addObserver($this,'urlCrawled','urlCrawled');
 *		$dispatcher->addObserver($this,'invokeQueueChange','invokeQueueChange');
 * 		$dispatcher->addObserver($this,'contentChange','contentChange');
 *	}
 *
 * The dispatcher is a singleton. The instance can be retrieved by:
 *
 * EventDispatcher::getInstance();
 *
 * Events can be posted by EventDispatcher::getInstance()->post('myEvent','eventGroup', array('foo' => 'bar'));
 */
class EventDispatcher
{
    /**
     * @var array
     */
    protected $observers;

    /**
     * @var EventDispatcher
     */
    protected static $instance;

    /**
     * The __constructor is private because the dispatcher is a singleton
     *
     * @return void
     *
     * @deprecated since crawler v6.3.0, will be removed in crawler v7.0.0.
     */
    protected function __construct()
    {
        $this->observers = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/domain/events/class.tx_crawler_domain_events_dispatcher.php']['registerObservers'] as $classRef) {
                $hookObj = &GeneralUtility::getUserObj($classRef);
                if (method_exists($hookObj, 'registerObservers')) {
                    $hookObj->registerObservers($this);
                }
            }
        }
    }

    /**
     * Returns all registered event types.
     *
     * @return array array with registered events.
     */
    protected function getEvents()
    {
        return array_keys($this->observers);
    }

    /**
     * This method can be used to add an observer for an event to the dispatcher
     *
     * @param EventObserverInterface $observer_object
     * @param string $observer_method
     * @param string $event
     *
     * @return void
     */
    public function addObserver(EventObserverInterface $observer_object, $observer_method, $event)
    {
        $this->observers[$event][] = ['object' => $observer_object, 'method' => $observer_method];
    }

    /**
     * Enables checking whether a certain event is observed by anyone
     *
     * @param string $event
     *
     * @return boolean
     */
    public function hasObserver($event)
    {
        return isset($this->observers[$event]) && count($this->observers[$event]) > 0;
    }

    /**
     * This method should be used to post a event to the dispatcher. Each
     * registered observer will be notified about the event.
     *
     * @param string $event
     * @param string $group
     * @param mixed $attachedData
     *
     * @return void
     */
    public function post($event, $group, $attachedData)
    {
        if (is_array($this->observers[$event])) {
            foreach ($this->observers[$event] as $eventObserver) {
                call_user_func([$eventObserver['object'],$eventObserver['method']], $event, $group, $attachedData);
            }
        }
    }

    /**
     * Returns the instance of the dispatcher singleton
     *
     * @return EventDispatcher
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof EventDispatcher) {
            $dispatcher = new EventDispatcher();
            self::$instance = $dispatcher;
        }

        return self::$instance;
    }
}
