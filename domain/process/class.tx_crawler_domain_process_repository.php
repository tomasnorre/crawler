<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process_collection.php';

require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_repository.php';


class tx_crawler_domain_process_repository extends tx_crawler_domain_lib_abstract_repository{
	
	protected $objectClassname = 'tx_crawler_domain_process';
	
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
	public function findAll($orderField = '',$orderDirection = 'DESC', $itemcount = NULL, $offset = NULL, $where = ''){
		
		$limit = self::getLimitFromItemCountAndOffset($itemcount,$offset);
		$db = $this->getDB();	
		
		$orderby 	= htmlspecialchars($orderField).' '.htmlspecialchars($orderDirection);
		$groupby 	= '';
		
		$rows = $db->exec_SELECTgetRows('*',$this->tableName,$where,$groupby,$orderby,$limit);
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
	public function countAll($where = '1=1'){
		return $this->countByWhere($where);
	}

	/**
	 * Returns the number of active processes
	 * @return int
	 */
	public function countActive(){
		return $this->countByWhere('active=1');
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
}
?>