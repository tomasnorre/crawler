<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process_collection.php';


class tx_crawler_domain_process_repository{
	
	/**
	 * This method is used to find all cli processes within a limit
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $where
	 * @param string $orderby
	 * @return tx_crawler_domain_process_collection a collection of process objects
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 */
	public function findAll($orderField = '',$orderDirection = 'DESC', $itemcount = NULL, $offset = NULL){
		
		$limit = self::getLimitFromItemCountAndOffset($itemcount,$offset);
		$db = $this->getDB();	
		
		$orderby 	= htmlspecialchars($orderField).' '.htmlspecialchars($orderDirection);
		$where 		= '';
		$groupby 	= '';
		
		$rows = $db->exec_SELECTgetRows('*','tx_crawler_process',$where,$groupby,$orderby,$limit);
		$processes = array();
		
		if(is_array($rows)){
			
			foreach($rows as $row){
				$process = new tx_crawler_domain_process($row);
				$processes[] = $process;
			}
		}
		
		return new tx_crawler_domain_process_collection($processes);
	}
	
	/**
	 * This method is used to count all processes in the process table
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return int
	 */
	public function countAll(){
		$db 	= $this->getDB();
		$rs 	= $db->exec_SELECTquery('count(*) as anz','tx_crawler_process','1=1');
		$res 	= $db->sql_fetch_assoc($rs);

		return $res['anz']; 
	}
	
	
	/**
	 * Get limit clause
	 *
	 * @param int item count
	 * @param int offset
	 * @return string limit clause
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 */
	public static function getLimitFromItemCountAndOffset($itemcount, $offset) {
		$limit = '';
		if (!empty($offset)) {
			$limit .= intval($offset).',';
		}
		if (!empty($itemcount)) {
			$limit .= intval($itemcount);
		}
		return $limit;
	}
		
	/**
	 * Returns an instance of the TYPO3 database class.
	 *
	 * @return  t3lib_DB
	 */
	protected function getDB(){
		return 	$GLOBALS['TYPO3_DB'];
		
	}
}
?>