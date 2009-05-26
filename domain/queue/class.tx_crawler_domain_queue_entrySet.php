<?php
/**
 * An Entry set represents a collection of queue entrys with an id to group
 * those queue entrys.
 *
 */
class tx_crawler_domain_queue_entrySet extends tx_crawler_domain_queue_entryCollection {
	/**
	 * Holds the id of this entrySet
	 * @var int
	 */
	protected $setId;
	
	
	
	/**
	 * @return int
	 */
	public function getSetId() {
		return $this->setId;
	}
	
	/**
	 * @param int $setId
	 */
	public function setSetId($setId) {
		$this->setId = $setId;
	}

}
?>