<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE media (dev@aoemedia.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

class tx_crawler_view_process_list {

	/**
	 * @var string template path
	 */
	protected $template = 'EXT:crawler/template/process/list.php';

	/**
	 * @var string icon path
	 */
	protected $iconPath;

	/**
	 * @var string Holds the path to start a cli process via command line
	 */
	protected $cliPath;

	/**
	 * @var int Holds the total number of items pending in the queue to be processed
	 */
	protected $totalItemCount;

	/**
	 * @var boolean Holds the enable state of the crawler
	 */
	protected $isCrawlerEnabled;

	/**
	 * @var int Holds the number of active processes
	 */
	protected $activeProcessCount;

	/**
	 * @var int Holds the number of maximum active processes
	 */
	protected $maxActiveProcessCount;

	/**
	 * @var string Holds the mode state, can be simple or detail
	 */
	protected $mode;

	/**
	 * @var int Holds the current page id
	 */
	protected $pageId;

	/**
	 * @var int $totalItemCount number of total item
	 */
	protected $totalUnprocessedItemCount;

	/**
	 * @var int Holds the number of assigned unprocessed items
	 */
	protected $assignedUnprocessedItemCount;

	/**
	 * @return int
	 */
	public function getAssignedUnprocessedItemCount() {
		return $this->assignedUnprocessedItemCount;
	}

	/**
	 * @return int
	 */
	public function getTotalUnprocessedItemCount() {
		return $this->totalUnprocessedItemCount;
	}

	/**
	 * @param int $assignedUnprocessedItemCount
	 */
	public function setAssignedUnprocessedItemCount($assignedUnprocessedItemCount) {
		$this->assignedUnprocessedItemCount = $assignedUnprocessedItemCount;
	}

	/**
	 * @param int $totalUnprocessedItemCount
	 */
	public function setTotalUnprocessedItemCount($totalUnprocessedItemCount) {
		$this->totalUnprocessedItemCount = $totalUnprocessedItemCount;
	}

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
	public function setIconPath($iconPath) {
		$this->iconPath = $iconPath;
	}

	/**
	 * Method to read the configured icon path
	 *
	 * @return string
	 */
	protected function getIconPath() {
		return $this->iconPath;
	}

	/**
	 * Method to set a collection of process objects to be displayed in
	 * the list view.
	 *
	 * @param tx_crawler_domain_process_collection $processCollection
	 */
	public function setProcessCollection($processCollection) {
		$this->processCollection = $processCollection;
	}

	/**
	 * Returns a collection of processObjects.
	 *
	 * @return tx_crawler_domain_process_collection
	 */
	protected function getProcessCollection() {
		return $this->processCollection;
	}

	/**
	 * Formats a timestamp as date
	 *
	 * @param int $timestamp
	 * @return string
	 */
	protected function asDate($timestamp) {
		if ($timestamp > 0) {
			return date($this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:time.detailed'), $timestamp);
		} else {
			return '';
		}
	}

	/**
	 * Converts seconds into minutes
	 *
	 * @param int $seconds
	 * @return int
	 */
	protected function asMinutes($seconds) {
		return round($seconds / 60);
	}

	/**
	 * Returns the state icon for the current job
	 *
	 * @param string $state
	 * @return string icon
	 */
	protected function getIconForState($state) {
		switch($state) {
			case 'running':
				$icon = 'bullet_orange';
				$title = $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.process.running');
				break;
			case 'completed':
				$icon = 'bullet_green';
				$title = $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.process.success');
				break;
			case 'cancelled':
				$icon = 'bullet_red';
				$title = $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.process.cancelled');
				break;
		}

		return $this->getIcon($icon, $title);
	}

	/**
	 * Returns a tag for the refresh icon
	 *
	 * @return string
	 */
	protected function getRefreshIcon() {
		return $this->getIcon('arrow_refresh');
	}

	/**
	 * Returns a tag for the refresh icon
	 *
	 * @return string
	 */
	protected function getRefreshLink() {
		return '<input onclick="window.location=\'' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_info') . '&SET[crawlaction]=multiprocess' . '\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'arrow_refresh.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.refresh') . '" />';
	}

	/**
	 * Returns an icon to stop all processes
	 *
	 * @return string html tag for stop icon
	 */
	protected function getStopIcon() {
		return $this->getIcon('stop');
	}

	/**
	 * Returns a link for the panel to enable or disable the crawler
	 *
	 * @return string
	 */
	protected function getEnableDisableLink() {
		if ($this->getIsCrawlerEnabled()) {
			return '<input onclick="window.location+=\'&action=stopCrawling\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'control_stop_blue.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.disablecrawling') . '" />';
		} else {
			return '<input onclick="window.location+=\'&action=resumeCrawling\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'control_play.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.enablecrawling') . '" />';
		}
	}

	/**
	 * Get mode link
	 *
	 * @param void
	 * @return string a-tag
	 */
	protected function getModeLink() {
		if ($this->getMode() == 'detail') {
			return '<input onclick="window.location+=\'&SET[processListMode]=simple\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'arrow_in.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.show.running') . '" />';
		} elseif ($this->getMode() == 'simple') {
			return '<input onclick="window.location+=\'&SET[processListMode]=detail\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'arrow_out.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.show.all') . '" />';
		}
	}

	/**
	 * Get add link
	 *
	 * @param void
	 * @return string a-tag
	 */
	protected function getAddLink() {
		if ($this->getActiveProcessCount() < $this->getMaxActiveProcessCount() && $this->getIsCrawlerEnabled()) {
			return '<input onclick="window.location+=\'&action=addProcess\';" type="button" style="padding:4px 4px 4px 20px; background-position: 3px 3px; background-image: url(\'' . $this->getIconPath() . 'add.png' . '\'); background-repeat: no-repeat;" value="' . $this->getLLLabel('LLL:EXT:crawler/modfunc1/locallang.xml:labels.process.add') . '" />';
		} else {
			return '';
		}
	}

	/**
	 * Returns the icon to add new crawler processes
	 *
	 * @return string html tag for image to add new processes
	 */
	protected function getAddIcon() {
		return $this->getIcon('add');
	}

	/**
	 * Returns an imagetag for an icon
	 *
	 * @param string $icon
	 * @return string html tag for icon
	 */
	protected function getIcon($icon, $title='') {
		if (!empty($title)) {
			$title = ' title="'.$title.'"';
		}
		return '<img src="'.$this->getIconPath().$icon.'.png" ' . $title . ' />';
	}

	/**
	 * Method to render the view.
	 *
	 * @return string html content
	 */
	public function render() {
		ob_start();
		$this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->template);
		include($this->template);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * retrieve locallanglabel from environment
	 * just a wrapper should be done in a cleaner way
	 * later on
	 *
	 * @param $label
	 * @return string
	 */
	protected function getLLLabel($label) {
		return $GLOBALS['LANG']->sL($label);
	}
}

?>
