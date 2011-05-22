<html>
<head>
	<title>Sample Comics for Sale</title>
</head>
<body>
<?
	// A sample collecting all comic sale offerings from a url and its
	// next urls as determined by navigation (pages 2, 3, ..)
	// then render the results as one big list of comics for sale
	//
	// The results will also be matched against a catalog of titles
	// using very basic extractor utility function to find closest 
	// string match. Idea is to expand the functionality of 
	
	require_once('WebXtractor/Loader.php');
 	
 	$strQuery 			= 'kuifje';
 	$strUrl 				= 'http://kopen.marktplaats.nl/search.php?xl=1&ds=to%3A1%3Bdi%3A%3Blt%3Azip%3Bsfds%3A1%3Bpt%3A0%3Bmp%3Anumeric%3Bl2%3A227%3Bkw%3A'. urlencode($strQuery) . '%3Bosi%3A2&ppu=0&p=1';
 	$pagesToFollow 	= 3;
 	
	$Runner = new WebXtractor_Runner();
	$arrResult = $Runner->index(
		$strUrl, 
		WebXtractor_Runner::FILTER_LINKS, 
		$pagesToFollow);
	
	if (!is_array($arrResult) || !count($arrResult['offers'])) {
		die ("no results");
	}

	// As bonus we will match found link titles against a catalog.
	// When there is a match, the catalog title will be displayed in 
	// result column..
	$comicTitles = array(
 		'in afrika', 'in amerika', 'sigaren farao', 'blauwe lotus',
 		'gebroker oor', 'zwarte rotsen', 'scepter van ottokar', 
 		'krab met gulden scharen', 'geheimzinnige ster', 'geheim van de eenhoorn',
 		'schat van scharlaken rakham', '7 kristallen bollen', 'zonnetempel',
 		'en het zwarte goud', 'raken naar de maan', 'mannen op de maan',
 		'zaak zonnebloem', 'cokes in voorraad', 'in tibet', 'juwelen van bianca castafiore',
 		'vlucht 714', 'en de picaro\'s', 'alfa-kunst');
 		
	// Render minimal html to display gallery
	header("Content-type: text/html;charset=utf-8");
	print "<b>" . (isset($arrResult['meta']) ? $arrResult['meta']->getTitle() : (count($arrResult['offers']) . 'results')) . "</b><hr noshade/><table>";
	foreach($arrResult['offers'] as $wxOffer) {
		print "<tr><td valign=\"top\">";
		if ($wxOffer->getLink()) 	print "<a href=\"" . $wxOffer->getLink() . "\">";
		if ($wxOffer->getTitle()) {
			print $wxOffer->getTitle();
		} else {
			print $wxOffer->getLink();
		}
		if ($wxOffer->getLink()) 	print "</a>";
		print "</td><td valign=\"top\">";
		
		// Matching to catalog titles
		$res 		= WebXtractor_Util_Matcher::getInstance('nl')->matchStrings($wxOffer->getTitle(), $comicTitles);
		if (!is_null($res) && isset($res['item'])) {
			print $res['item'];
		}
		
		print "</td><td>";
		print $wxOffer->getText();
		print "</td></tr>\n";
	}
	print "</table>";
?>
</body>
</html>