<?php

class tx_crawler_view_process_list {

	protected $template = 'EXT:crawler/template/process/list.php';

	protected $iconPath;

	/**
	 * Holds the path to start a cli process via command line
	 *
	 * @var string
	 */
	protected $cliPath;

	/**
	 * Holds the total number of items pending in the queue to be processed
	 *
	 * @var int
	 */
	protected $totalItemCount;

	/**
	 * Holds the enable state of the crawler
	 *
	 * @var boolean
	 */
	protected $isCrawlerEnabled;


	/**
	 * Holds the number of active processes
	 *
	 * @var int
	 */
	protected $activeProcessCount;


	/**
	 * Holds the number of maximum active processes
	 *
	 * @var int
	 */
	protected $maxActiveProcessCount;

	/**
	 * Holds an internal message, when an action has been performed
	 *
	 * @var string
	 */
	protected $actionMessage;


	/**
	 * Holds the mode state, can be simple or detail
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Holds the current page id
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 * Set the page id
	 *
	 * @param int page id
	 */
	public function setPageId($pageId) {
		$this->pageId = $pageId;
	}

	/**
	 * Get the page id
	 *
	 * @return int page id
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * @param string $mode
	 */
	public function setMode($mode) {
		$this->mode = $mode;
	}


	/**
	 * @return string
	 */
	public function getActionMessage() {
		return $this->actionMessage;
	}

	/**
	 * @param string $actionMessage
	 */
	public function setActionMessage($actionMessage) {
		$this->actionMessage = $actionMessage;
	}


	/**
	 * @return int
	 */
	public function getMaxActiveProcessCount() {
		return $this->maxActiveProcessCount;
	}

	/**
	 * @param int $maxActiveProcessCount
	 */
	public function setMaxActiveProcessCount($maxActiveProcessCount) {
		$this->maxActiveProcessCount = $maxActiveProcessCount;
	}


	/**
	 * @return int
	 */
	public function getActiveProcessCount() {
		return $this->activeProcessCount;
	}

	/**
	 * @param int $activeProcessCount
	 */
	public function setActiveProcessCount($activeProcessCount) {
		$this->activeProcessCount = $activeProcessCount;
	}

	/**
	 * @return boolean
	 */
	public function getIsCrawlerEnabled() {
		return $this->isCrawlerEnabled;
	}

	/**
	 * @param boolean $isCrawlerEnabled
	 */
	public function setIsCrawlerEnabled($isCrawlerEnabled) {
		$this->isCrawlerEnabled = $isCrawlerEnabled;
	}


	/**
	 * Returns the path to start a cli process from the shell
	 * @return string
	 */
	public function getCliPath() {
		return $this->cliPath;
	}

	/**
	 * @param string $cliPath
	 */
	public function setCliPath($cliPath) {
		$this->cliPath = $cliPath;
	}


	/**
	 * @return int
	 */
	public function getTotalItemCount() {
		return $this->totalItemCount;
	}

	/**
	 * @param int $totalItemCount
	 */
	public function setTotalItemCount($totalItemCount) {
		$this->totalItemCount = $totalItemCount;
	}

	/**
	 * Method to set the path to the icon from outside
	 *
	 * @param string $iconPath
	 */
	public function setIconPath($iconPath){
		$this->iconPath = $iconPath;
	}

	/**
	 * Method to read the configured icon path
	 *
	 * @return string
	 */
	protected function getIconPath(){
		return $this->iconPath;
	}

	/**
	 * Method to set a collection of process objects to be displayed in
	 * the list view.
	 *
	 * @param tx_crawler_domain_process_collection $processCollection
	 */
	public function setProcessCollection($processCollection){
		$this->processCollection = $processCollection;
	}

	/**
	 * Returns a collection of processObjects.
	 *
	 * @return tx_crawler_domain_process_collection
	 */
	protected function getProcessCollection(){
		return $this->processCollection;
	}

	/**
	 * Formats a timestamp as date
	 *
	 * @param int $timestamp
	 * @return string
	 */
	protected function asDate($timestamp){
		if($timestamp > 0){
			return date("H:i:s / d.m.Y", $timestamp);
		}else{
			return '';
		}
	}

	/**
	 * Converts seconds into minutes
	 *
	 * @param int $seconds
	 * @return int
	 */
	protected function asMinutes($seconds){
		return round($seconds / 60);
	}


	/**
	 * Returns the state icon for the current job
	 *
	 * @param string $state
	 */
	protected function getIconForState($state){
		switch($state){
			case 'running':	$icon = 'bullet_orange'; break;
			case 'completed': $icon = 'bullet_green'; break;
			case 'canceled': $icon = 'bullet_red'; break;
		}

		return $this->getIcon($icon);
	}

	/**
	 * Returns a tag for the refresh icon
	 *
	 * @return string
	 */
	protected function getRefreshIcon(){
		return $this->getIcon('arrow_refresh');
	}

	/**
	 * Returns a tag for the refresh icon
	 *
	 * @return string
	 */
	protected function getRefreshLink(){
		return '<a href="index.php?id='.$this->pageId.'" title="Refresh">' . $this->getRefreshIcon() . '</a>';
	}

	/**
	 * Returns an icon to stop all processes
	 *
	 * @return string html tag for stop icon
	 */
	protected function getStopIcon(){
		return $this->getIcon('stop');
	}

	/**
	 * Returns a link for the panel to enable or disable the crawler
	 *
	 * @return string
	 */
	protected function getEnableDisableLink(){
		if($this->getIsCrawlerEnabled()){
			return '<a href="index.php?id='.$this->pageId.'&action=stopCrawling" title="Stop all processes and disable crawling">'.$this->getIcon('control_stop_blue').'</a>';
		}else{
			return '<a href="index.php?id='.$this->pageId.'&action=resumeCrawling" title="Enable crawling">'.$this->getIcon('control_play').'</a>';
		}
	}


	protected function getModeLink() {
		if ($this->getMode() == 'detail') {
			return '<a href="index.php?id='.$this->pageId.'&SET[processListMode]=simple" title="Show only running processes">'.$this->getIcon('arrow_in').'</a>';
		} elseif($this->getMode() == 'simple') {
			return '<a href="index.php?id='.$this->pageId.'&SET[processListMode]=detail" title="Show finished and terminated processes">'.$this->getIcon('arrow_out').'</a>';
		}
	}

	/**
	 *
	 */
	protected function getAddLink(){
		if($this->getActiveProcessCount() < $this->getMaxActiveProcessCount() && $this->getIsCrawlerEnabled()){
			return '<a href="index.php?id='.$this->pageId.'&action=addProcess" title="Add process">'.$this->getAddIcon().'</a>';
		}else{
			return '';
		}
	}

	/**
	 * Returns the icon to add new crawler processes
	 *
	 * @return string html tag for image to add new processes
	 */
	protected function getAddIcon(){
		return $this->getIcon('add');
	}

	/**
	 * Returns an imagetag for an icon
	 *
	 * @param string $icon
	 * @return string html tag for icon
	 */
	protected function getIcon($icon){
		return '<img src="'.$this->getIconPath().$icon.'.png" />';
	}

	/**
	 * Method to render the view.
	 *
	 * @return string html content
	 */
	public function render(){
		ob_start();
		$this->template = t3lib_div::getFileAbsFileName($this->template);
		include($this->template);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}
?>