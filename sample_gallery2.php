<html>
<head>
	<title>Sample Gallery 2</title>
</head>
<body>
<?
	// A sample collecting all (relevant) images from a url and its
	// next urls as determined by navigation (pages 2, 3, ..)
	// then render the results as one big gallery
	//
	// This implementation differs from sample_gallery.php in that
	// outputs the extracted item directly from the parser.
	// When parsing a lot of items from multiple pages, this allows 
	// for flushing the results as early as possible to browser.
	
	require_once('WebXtractor/Loader.php');
 	
 	class GalleryViewer implements WebXtractor_Extractor_Receiver_Interface {
 		protected $meta									= null;
		protected $offers								= array();
		
		public function view($url, $pagesToFollow = 1) {
	 		$wxExtractor 	= new WebXtractor_Extractor_Html($this);	
	 		$wxExtractor->setAllowLinkBlockOffers(0);
			$wxExtractor->setAllowImageBlockOffers(1);
			$wxExtractor->setMinImageBlockOffers(6);
			
	 		$wxUrl 				= new WebXtractor_Net_Url($url);
			$wxIndexer 		= new WebXtractor_Indexer();
			$wxIndexer->setMaxFollowPaginatedLinks($pagesToFollow);
			
			$wxIndexer->index($wxUrl, $wxExtractor);
		}			
 	
 		public function onOffer(WebXtractor_Extractor_Object &$wxOffer) {
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
			flush();
			return true;
		}
		
		public function onMeta(WebXtractor_Extractor_Meta &$wxMeta) {
			if (is_null($this->meta)) { 
				$this->meta = $wxMeta;
				print "<b>" . $wxMeta->getTitle() . "</b><hr noshade/>";
			}
		}
 	}
 	
 	$strUrl 				= 'http://www.flickr.com/groups/funnyfunnyfaces/pool/page1/';
 	$pagesToFollow 	= 3;
 	
 	$GalleryViewer = new GalleryViewer();
 	$GalleryViewer->view($strUrl, $pagesToFollow);
?>
</body>
</html>