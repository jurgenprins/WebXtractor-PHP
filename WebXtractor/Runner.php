<?
	// An example runner.. it only parses Html pages and implements
	//  a predefined configuration to look for link blocks or image galleries
	
	class WebXtractor_Runner implements WebXtractor_Extractor_Receiver_Interface {
		const DEFAULT_NUM_FOLLOW_PAGES 	= 2;
		
		// Some predefined filters that configure the extractors for specific purpose
		const FILTER_LINKS 							= 1;
		const FILTER_GALLERY						= 2;
		
		protected $meta									= null;
		protected $offers								= array();
	
		function index($url, $filter = FILTER_LINKS, $num_follow_pages = DEFAULT_NUM_FOLLOW_PAGES) {
			// For now, just hardwire the Html extractor..
			$wxExtractor = new WebXtractor_Extractor_Html($this);
			
			// Convenience filters configuring the extractors
			switch($filter) {
				case self::FILTER_GALLERY:
					$wxExtractor->setAllowLinkBlockOffers(0);
					$wxExtractor->setAllowImageBlockOffers(1);
					$wxExtractor->setMinImageBlockOffers(6);
					break;
				default:
					$wxExtractor->setAllowLinkBlockOffers(1);
					$wxExtractor->setMinLinkBlockOffers(10);
					$wxExtractor->setAllowImageBlockOffers(0);
			}
			
			$wxUrl = new WebXtractor_Net_Url($url);
			
			$wxIndexer = new WebXtractor_Indexer();
			$wxIndexer->setMaxFollowPaginatedLinks($num_follow_pages);
			
			$wxIndexer->index($wxUrl, $wxExtractor);
			
			// the indexer has now called onOffer/onMeta 
			// filling the class member variables offers and meta
			
			// we will just return them here
			// a client can also extend the runner and implement their 
			//  own onMeta and onOffer to e.g. render offers as they come in
			
			return array('meta' => $this->meta, 'offers' => $this->offers);
		}
		
		public function onOffer(WebXtractor_Extractor_Object &$wxOffer) {
			$this->offers[$wxOffer->hashCode()]	= $wxOffer;
			return true;
		}
		
		public function onMeta(WebXtractor_Extractor_Meta &$wxMeta) {
			if (is_null($this->meta)) {
				$this->meta			= $wxMeta;	
			}
		}
	}
?>