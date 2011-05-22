<?
	// A sample collecting all entries from an rss feed
	// then render the results as itemlist
	//
	// The Feed Extractor needs Zend_Feed_Reader to be available 
	// in lib path, as it does not implement rss parsing itself
	//
	// The Feed Extractor really just transforms output of feed
	// to normalized webxtractor items, so one can aggregate offers
	// from html extractions and feed extractions into one single
	// collection easily

	$strQuery = 'kuifje';
	$strUrl 	= 'http://kopen.marktplaats.nl/opensearch.php?b=1&q=' . $strQuery . '&ts=1&pa=1&s=10';	
	
	require_once('WebXtractor/Loader.php');
 	
 	class RssReader implements WebXtractor_Extractor_Receiver_Interface {
 		protected $meta									= null;
		protected $offers								= array();
		
		public function getFeed($url) {
	 		$wxExtractor 	= new WebXtractor_Extractor_Feed($this);	
	 		$wxUrl 				= new WebXtractor_Net_Url($url);
			$wxIndexer 		= new WebXtractor_Indexer();
			
			$wxIndexer->index($wxUrl, $wxExtractor);
			
			return array('meta' => $this->meta, 'offers' => $this->offers);
		}			
 	
 		public function onOffer(WebXtractor_Extractor_Object &$wxOffer) {
			$this->offers[$wxOffer->hashCode()]	= $wxOffer;
			return true;
		}
		
		public function onMeta(WebXtractor_Extractor_Meta &$wxMeta) {
			if (is_null($this->meta)) { $this->meta			= $wxMeta;	}
		}
 	}
 	
	$RssReader = new RssReader();
	$arrResult = $RssReader->getFeed(FEED_URL);
	if (!is_array($arrResult) || !count($arrResult['offers'])) {
		die ("no results");
	}

	// Render minimal html to display gallery
	print "<html><title>" . (isset($arrResult['meta']) ? $arrResult['meta']->getTitle() : 'result') . "</title><body>";
	foreach($arrResult['offers'] as $wxOffer) {
		if ($wxOffer->getLink()) 	print "<a href=\"" . $wxOffer->getLink() . "\">";
		if ($wxOffer->getImage())	{
			print "<img src=\"" . $wxOffer->getImage() . "\" width=\"200\" ";
			if ($wxOffer->getTitle()) print "alt=\"" . $wxOffer->getTitle() . "\"";
			print "/>";
		} else if ($wxOffer->getTitle()) {
			print $wxOffer->getTitle();
		} else {
			print $wxOffer->getLink();
		}
		if ($wxOffer->getLink()) 	print "</a>";
		if (!$wxOffer->getImage()) print "<br>\n";
	}
	print "</body></html>";

?>