<?
	class WebXtractor_Extractor_Html extends WebXtractor_Extractor_Abstract {
		private $allowImageBlockOffers	= true;
		private $minImageBlockOffers 	= 1;
		
		private $allowLinkBlockOffers	= true;
		private $minLinkBlockOffers		= 1;
		
		function getOfferFromImage(&$wxUrl, $arrImage) {
			return new WebXtractor_Extractor_Object($wxUrl, $arrImage['href'], md5($arrImage['href']), $arrImage['src']);
 		}
 			
 		function getOfferFromLink(&$wxUrl, $arrLink)  {
 			$strTitle = '';
 			$strText = '';
			foreach($arrLink['texts'] as $arrText) {
				if (!$strTitle) {
					$strTitle = $arrText['text'];
				} else {
					$strText .= ($strText ? ' | ' : '') . $arrText['text'];
				}
			}
			return new WebXtractor_Extractor_Object($wxUrl, $arrLink['href'], $strTitle, '', $strText);
 		}
 			
 		function process(WebXtractor_Net_Url &$wxUrl, $strHtml) {
 			$numOffersIndexed = 0;
 			
 			// Parse html into structured page and walk through found items
 			$wxHtmlParser = new WebXtractor_Parser_Html($wxUrl, $strHtml);
 			
 			if ($strTitle = $wxHtmlParser->getTitle()) {
 				$this->processMeta(new WebXtractor_Extractor_Meta($wxUrl, $strTitle));
 			}
 			
 			if ($this->allowImageBlockOffers) {
 				foreach($wxHtmlParser->getImages() as $arrImageBlock) {
					if (!is_array($arrImageBlock['images']) ||
					    (count($arrImageBlock['images']) < $this->minImageBlockOffers)) {
						continue;
					}
					
					foreach($arrImageBlock['images'] as $arrImage) {
						$wxOffer = $this->getOfferFromImage($wxUrl, $arrImage);
						if (is_null($wxOffer)) {
							continue;
						}
						
						$numOffersIndexed += ($this->processOffer($wxOffer) ? 1 : 0);
					}
					
					break;
				}
			}
			
			if ($this->allowLinkBlockOffers) {
				foreach($wxHtmlParser->getLinks() as $arrLinkBlock) {
					if (!is_array($arrLinkBlock['links']) ||
					    (count($arrLinkBlock['links']) < $this->minLinkBlockOffers)) {
						continue;
					}
					
					foreach($arrLinkBlock['links'] as $arrLink) {
						$wxOffer = $this->getOfferFromLink($wxUrl, $arrLink);
						if (is_null($wxOffer)) {
							continue;
						}
						
						$numOffersIndexed += ($this->processOffer($wxOffer) ? 1 : 0);
					}
					
					break;
				}
			}
			
			WebXtractor_Logger::debug("HTML EXTRACTOR: " . $numOffersIndexed . ' OFFERS PROCESSED');
			
			return $wxHtmlParser;
		}
	
		function getNextLinks($wxHtmlParser) {
			$arrNextLinks = array();
			if (!is_null($arrPaginator = $wxHtmlParser->getPaginator())) {
				foreach($arrPaginator as $arrPaginatorOffer) {
					$arrNextLinks[] = $arrPaginatorOffer['href'];
				}
			}
			return $arrNextLinks;
		}	
	
		function getAllowImageBlockOffers() { return $this->allowImageBlockOffers; }
		function setAllowImageBlockOffers($allowImageBlockOffers) {
			$this->allowImageBlockOffers = $allowImageBlockOffers ? true : false;
		}
		
		function getMinImageBlockOffers() { return $this->minImageBlockOffers; }
		function setMinImageBlockOffers($minImageBlockOffers) {
			if (0 + $minImageBlockOffers) {
				$this->minImageBlockOffers = 0 + $minImageBlockOffers;
			}
		}
		
		function getAllowLinkBlockOffers() { return $this->allowLinkBlockOffers; }
		function setAllowLinkBlockOffers($allowLinkBlockOffers) {
			$this->allowLinkBlockOffers = $allowLinkBlockOffers ? true : false;
		}
		
		function getMinLinkBlockOffers() { return $this->minLinkBlockOffers; }
		function setMinLinkBlockOffers($minLinkBlockOffers) {
			if (0 + $minLinkBlockOffers) {
				$this->minLinkBlockOffers = 0 + $minLinkBlockOffers;
			}
		}
	}										
?>