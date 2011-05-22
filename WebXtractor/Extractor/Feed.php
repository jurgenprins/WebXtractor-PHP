<?
	if (!class_exists('Zend_Feed_Reader')) {
		die('Oops, WebXtractor_Extractor_Feed needs Zend_Feed_Reader, and Zend seems not installed or in library path');
	}
	
	class WebXtractor_Extractor_Feed extends WebXtractor_Extractor_Abstract {
		function getOfferFromFeedEntry(&$wxUrl, $entry) {
			return new WebXtractor_Extractor_Object($wxUrl, $entry->getLink(), $entry->getTitle(), '');
 		}
 		
		function process(WebXtractor_Net_Url &$wxUrl, $strData) {
			$arrOffersIndexed = array();
			
			$feed = Zend_Feed_Reader::importString($strData); 
			
			if ($strTitle = $feed->getTitle()) {
				$this->processMeta(new WebXtractor_Extractor_Meta($wxUrl, $strTitle));
			}
			
			foreach($feed as $entry) {
				$wxOffer = $this->getOfferFromFeedEntry($wxUrl, $entry);
				if (is_null($wxOffer)) {
					continue;
				}
				
				$arrOffersIndexed[] = $this->processOffer($wxOffer);
			}
			
			WebXtractor_Logger::debug("FEED EXTRACTOR: " . count($arrOffersIndexed) . ' OFFERS PROCESSED');
			
			$this->onOffersProcessed($wxUrl, array_filter($arrOffersIndexed));
			
			return $feed;
		}
		
		function getNextLinks($feed) {
			return array();
		}
	}
?>