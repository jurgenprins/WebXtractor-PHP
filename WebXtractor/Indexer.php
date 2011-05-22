<?
	ini_set('max_execution_time', 3600);
	
 	class WebXtractor_Indexer {
 		private $wxUrlReader 					= null;
 		public 	$wxIndexBoard					= null;
 		
 		public $intMaxFollowPaginatedLinks = 0;
 			
 		function __construct() {
 			$this->wxUrlReader 				= new WebXtractor_Net_UrlReader();
 			$this->wxIndexBoard				= new WebXtractor_Indexer_Board();
 		}
 
 		function onWxUrlReadSuccess (WebXtractor_Extractor_Abstract $wxOfferExtractor) {
 			$self = &$this;  // http://wiki.php.net/rfc/closures/removal-of-this
 					
 			return function ($wxUrl, $strHtml) use (&$self, $wxOfferExtractor) {
 				WebXtractor_Logger::debug("LIB INDEX: READ SUCCESS " . $wxUrl->getUrl() . ' ' . strlen($strHtml) . ' bytes');
 				
				if (!($res = $wxOfferExtractor->process($wxUrl, $strHtml))) {
					$self->wxIndexBoard->setStatus($wxUrl->getUrl(), WebXtractor_Indexer_Board::STATUS_FAILED);
				}
				
				$self->wxIndexBoard->setStatus($wxUrl->getUrl(), WebXtractor_Indexer_Board::STATUS_PROCESSED);
			
				if ($self->wxIndexBoard->size() <= $self->intMaxFollowPaginatedLinks) {
					// Get next links to follow for further indexing
					$arrLinksToFollow = $wxOfferExtractor->getNextLinks($res);
					WebXtractor_Logger::debug('LIB INDEX: ' . count($arrLinksToFollow) . ' NEXT LINKS FOUND');
					
					foreach($arrLinksToFollow as $strLinkToFollow) {
						if ($self->wxIndexBoard->contains($strLinkToFollow)) continue;
						if ($self->wxIndexBoard->size() > $self->intMaxFollowPaginatedLinks) break;
						
						WebXtractor_Logger::debug('LIB INDEX: NEXT LINK ' . $strLinkToFollow . ' QUEUED TO BOARD ' . $self->wxIndexBoard->id);
						$self->wxIndexBoard->setStatus($strLinkToFollow, WebXtractor_Indexer_Board::STATUS_QUEUED);
					}
				}
			};
		}
		
		function onWxUrlReadFailed () {
 			$self = &$this;  // http://wiki.php.net/rfc/closures/removal-of-this
 					
 			return function ($wxUrl, $strErr) use (&$self) {
 				WebXtractor_Logger::debug("LIB INDEX: READ FAILED " . $wxUrl->getUrl() . ': ' . $strErr);
 				$self->wxIndexBoard->setStatus($wxUrl->getUrl(), WebXtractor_Indexer_Board::STATUS_FAILED);
 			};
 		}
 				
 		function index($mixedWxUrl, WebXtractor_Extractor_Abstract $wxOfferExtractor) {
 			if (!is_array($mixedWxUrl))	$mixedWxUrl = array($mixedWxUrl);
 			
 			$arrWxUrlsToIndex = array();
			foreach ($mixedWxUrl as $wxUrl) {
				if ($this->wxIndexBoard->getStatus($wxUrl->getUrl()) == WebXtractor_Indexer_Board::STATUS_PROCESSED) {
				 	continue;
				}
				
				$arrWxUrlsToIndex[] = $wxUrl;
 			}
 			
 			if (!count($arrWxUrlsToIndex)) return 0;
 			
 			WebXtractor_Logger::debug('LIB INDEX START..: ' . count($arrWxUrlsToIndex) . ' URLS');
 			
 			$intNumProcessed = $this->wxUrlReader->fetch(
 				$arrWxUrlsToIndex, 
 				$this->onWxUrlReadSuccess($wxOfferExtractor),
 				$this->onWxUrlReadFailed());
 			
			WebXtractor_Logger::debug('LIB INDEX DONE..: ' . $arrWxUrlsToIndex[0]->getUrl());
 			
 			// Prepare all (still/new) queued urls for (recursive) indexing
 			$arrWxUrlsToIndex = array();
 			foreach($this->wxIndexBoard->getAll() as $strUrl => $enumStatus) {
 				switch($enumStatus) {
 					case WebXtractor_Indexer_Board::STATUS_QUEUED:
 						$arrWxUrlsToIndex[] = new WebXtractor_Net_Url($strUrl);
 						break;
 					default:
 				}
 			}
 			
 			WebXtractor_Logger::debug('LIB INDEX FINISH..: ' . count($arrWxUrlsToIndex) . ' TO FOLLOW');
 			
 			return $intNumProcessed + $this->index($arrWxUrlsToIndex, $wxOfferExtractor);			
 		}
 		
 		function setMaxFollowPaginatedLinks($intMaxFollowPaginatedLinks) {
 			$this->intMaxFollowPaginatedLinks = $intMaxFollowPaginatedLinks;
 		}
 	}
 ?>