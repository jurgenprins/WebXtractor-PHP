<?
	class WebXtractor_Indexer_Map {
		private $map = array();
		
		function put($strKey, $val) {
			$this->map[$strKey] = $val;
		}
		
		function get($strKey) {
			return array_key_exists($strKey, $this->map) ? $this->map[$strKey] : null;
		}
		
		function contains($strKey) {
			return array_key_exists($strKey, $this->map);
		}
		
		function find() {
			$a = array();
			foreach($this->map as $strKey => $val) {
				$a[] = array('url' => $strKey, 'status' => $val['status']);
			}
			return $a;
		}
		
		function size() {
			return count(array_keys($this->map));
		}
		
		function drop() {
			$this->map = array();
		}
	}
		
 	class WebXtractor_Indexer_Board {
 		const 	STATUS_FAILED 				= -1;
 		const 	STATUS_NAVAIL 				= 0;
 		const 	STATUS_QUEUED 				= 1;
 		const 	STATUS_PROCESSED 			= 2;
 		
 		private $wxMap								= null;
 		public 	$id										= null;
 		
 		function __construct() {
 			$this->wxMap	= new WebXtractor_Indexer_Map();
 			$this->id			= uniqid('');
 		}
 		
 		function setStatus($strUrl, $enumStatus) {
 			WebXtractor_Logger::debug('INDEX BOARD: ' . $this->id . ' PUT ' . $strUrl . ' TO ' . WebXtractor_Indexer_Board::verboseStatus($enumStatus));
 			return $this->wxMap->put($strUrl, array('status' => $enumStatus));
 		}
 		
 		function getStatus($strUrl) {
 			$enumStatus = !is_null($arrRes = $this->wxMap->get($strUrl)) ? $arrRes['status'] : self::STATUS_NAVAIL;
 			WebXtractor_Logger::debug('INDEX BOARD: ' . $this->id . ' GET ' . $strUrl . ' AS ' . WebXtractor_Indexer_Board::verboseStatus($arrRes['status']));
 			return $enumStatus;
 		}
 		
 		static function verboseStatus($enumStatus) {
 			switch($enumStatus) {
 				case self::STATUS_FAILED: return 'FAILED';
 				case self::STATUS_QUEUED: return 'QUEUED';
 				case self::STATUS_PROCESSED: return 'PROCESSED';
 				default: return 'N/A';
 			}
 		}
 		
 		function contains($strUrl) {
 			$bolRes = $this->wxMap->contains($strUrl);
			WebXtractor_Logger::debug('INDEX BOARD: ' . $this->id . ' CONTAINS ' . $strUrl . ' = ' . ($bolRes ? 'YES' : 'NO'));
 			return $bolRes;
 		}
 		
 		function getAll() {
 			$arrAll = array();
 			foreach ($this->wxMap->find() as $arrEntry) {
 				$arrAll[$arrEntry['url']] = $arrEntry['status'];
 			}
 			return $arrAll;
 		}
 		
 		function size() {
 			$intRes = $this->wxMap->size();
 			WebXtractor_Logger::debug('INDEX BOARD: ' . $this->id . ' SIZE = ' . $intRes);
 			return $intRes;
 		}
 		
 		function __destruct() {
 			$this->wxMap->drop();
 		}
 	}
?>