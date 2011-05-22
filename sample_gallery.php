<html>
<head>
	<title>Sample Gallery</title>
</head>
<body>
<?
	// A sample collecting all (relevant) images from a url and its
	// next urls as determined by navigation (pages 2, 3, ..)
	// then render the results as one big gallery
	
	require_once('WebXtractor/Loader.php');
 	
 	$strUrl 				= 'http://www.flickr.com/groups/funnyfunnyfaces/pool/page1/';
 	$pagesToFollow 	= 3;
 	
	$Runner = new WebXtractor_Runner();
	$arrResult = $Runner->index(
		$strUrl, 
		WebXtractor_Runner::FILTER_GALLERY, 
		$pagesToFollow);
	
	if (!is_array($arrResult) || !count($arrResult['offers'])) {
		die ("no results");
	}

	// Render minimal html to display gallery
	print "<b>" . (isset($arrResult['meta']) ? $arrResult['meta']->getTitle() : (count($arrResult['offers']) . 'results')) . "</b><hr noshade/>";
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
?>
</body>
</html>