<?php
class tx_crawler_view_process_list{
	protected $template = 'EXT:crawler/template/process/list.php';
	
	protected $iconPath;
	
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
			return date("H:i:s / d m Y", $timestamp);
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