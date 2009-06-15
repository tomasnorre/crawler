<?php
class tx_crawler_view_pagination{
	protected $template = 'EXT:crawler/template/pagination.php';


	/**
	 * @var int $perpage number of items perPage
	 */
	protected $perPage;

	/**
	 * @var int $currentOffset current offset
	 */
	protected $currentOffset;

	/**
	 * @var int $totalItemCount number of total item
	 */
	protected $totalItemCount;

	/**
	 * @var string $baseUrl
	 */
	protected $baseUrl;




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

	/**
	 * Returns the currently configured offset-
	 * @return int
	 */
	public function getCurrentOffset() {
		return $this->currentOffset;
	}

	/**
	 * Method to read the number of items per page
	 *
	 * @return int
	 */
	public function getPerPage() {
		return $this->perPage;
	}

	/**
	 * Method to set the current offset from start
	 *
	 * @param int $currentOffset
	 */
	public function setCurrentOffset($currentOffset) {
		$this->currentOffset = $currentOffset;
	}

	/**
	 * Number of items per page.
	 *
	 * @param int $perPage
	 */
	public function setPerPage($perPage) {
		$this->perPage = $perPage;
	}

	/**
	 * returns the total number of items
	 * @return int
	 */
	public function getTotalItemCount() {
		return $this->totalItemCount;
	}

	/**
	 * Method to set the total number of items in the pagination
	 *
	 * @param int $totalItemCount
	 */
	public function setTotalItemCount($totalItemCount) {
		$this->totalItemCount = $totalItemCount;
	}

	/**
	 * Returns the total number of pages needed to  display all content which
	 * is paginatable
	 *
	 * @return int
	 */
	public function getTotalPagesCount(){
	 	return ceil($this->getTotalItemCount() / $this->getPerPage());
	}

	/**
	 * This method is used to caluclate the label for a pageoffset,
	 * in normal cases its the internal offset + 1
	 *
	 * @param int $pageoffset
	 * @return int
	 */
	protected function getLabelForPageOffset($pageoffset){
		return $pageoffset + 1;
	}


}
?>