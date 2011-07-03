<?
	class WebXtractor_Parser_Html {
		private $wxUrl = null;
		private $strHtml = null;
		private $dom = null;
		private $xpath = null;
		
		private $arrXPaths = null;
		private $arrNumXPaths = null;
		private $arrGroupedXPaths = null;
		
		private $arrStructure = null;
		private $arrImages = null;
		private $arrLinks = null;

		const 	PAGINATION_MIN_LINK_OCCURS = 4;
				
		function __construct(WebXtractor_Net_Url $wxUrl, $strHtml) {
			$this->wxUrl = $wxUrl;
			$this->strHtml = $strHtml;
			
			$html = str_replace(
				$arrTags = array('<b>','</b>','<em>','</em>', '<strong>', '</strong>', '<i>', '</i>'),
				array_fill(0, count($arrTags), ''),
				$this->strHtml);
				
			$this->dom = new DOMDocument();
			@$this->dom->loadHTML($html);
		
			$this->arrXPaths = $this->getXpaths($this->dom, '');
			$this->arrNumXPaths = array();
			$this->arrGroupedXPaths = array();
			foreach($this->arrXPaths as $strXPath) {
				@$this->arrNumXPaths[$strXPath]++;
			}
			foreach($this->arrNumXPaths as $strXPath => $intNumOccurs) {
				@$this->arrGroupedXPaths[$intNumOccurs][] = $strXPath;
			}
			$this->xpath = new DOMXpath($this->dom);
		}
		
		function getWxUrl() {
			return $this->wxUrl;
		}
		
		function getXpaths($node, $path) {
			$arrPaths = array();
			$child = $node->firstChild;
			while($child) {
				$arrPaths[] = ($newpath = "{$path}/{$child->nodeName}");
			
				if ($child->hasChildNodes()) {
					$arrPaths = array_merge($arrPaths, $this->getXpaths($child, $newpath));
				}
				
			  $child = $child->nextSibling;
			}
			return $arrPaths;
		}
	
		function getTitle() {
			$strTitle = '';
			$titleElements = @$this->xpath->query('//title');
			if (!is_null($titleElements) && $titleElements) {
				foreach($titleElements as $titleElement) {
					$strTitle = trim($titleElement->nodeValue);
					break;
				}
			}
			return $strTitle;
		}
		
	  function getStructure() {
	  	if ($this->arrStructure) return $this->arrStructure;
	  	
	  	$this->arrStructure = array(
	  		'links' => array(),
	  		'images' => array());
	  		
			foreach($this->arrGroupedXPaths as $intNumOccurs => $arrXPathsInGroup) {
				foreach($arrXPathsInGroup as $strElementXPath) {
					$strTag = strtolower(substr(strrchr($strElementXPath, '/'), 1));
					switch($strTag) {
						case 'a':
							$this->arrStructure['links'][$intNumOccurs][$strElementXPath] = array();
							$linkElements = @$this->xpath->query($strElementXPath);
							if (!is_null($linkElements) && $linkElements) {
				  			foreach ($linkElements as $linkElement) {
				  				if (($linkStyle = $linkElement->attributes->getNamedItem('style')) &&
				  				    (strstr($linkStyle->nodeValue, 'background-image'))) {
				  					$this->arrStructure['images'][$intNumOccurs][$strElementXPath . '[@style]'] = array();
				  				}
				  			}
				  		}
							break;
						case 'img':
							$this->arrStructure['images'][$intNumOccurs][$strElementXPath] = array();
							break;
						default:
					}
				}
				if (isset($this->arrStructure['links'][$intNumOccurs])) {
					krsort($this->arrStructure['links'][$intNumOccurs]);
				}
				if (isset($this->arrStructure['images'][$intNumOccurs])) {
					krsort($this->arrStructure['images'][$intNumOccurs]);
				}
			}
			krsort($this->arrStructure['links']);
			krsort($this->arrStructure['images']);
			
			// find text xpaths relative to links
			foreach($this->arrXPaths as $strTextXPath) {
				$strTag = strtolower(substr(strrchr($strTextXPath, '/'), 1));
				if (strcmp($strTag, '#text')) continue;
				foreach($this->arrStructure['links'] as $intNumOccurs => $arrLinkXPaths) {
					foreach($arrLinkXPaths as $strLinkXPath => $arrData) {
						// If text is in same block (xpath is below link xpath), include
						if (!strncmp($strLinkXPath, $strTextXPath, strlen($strLinkXPath))) {
							$strRelativeTextXPath = '.' . str_replace('#text', 'text()', substr($strTextXPath, strlen($strLinkXPath)));
							$this->arrStructure['links'][$intNumOccurs][$strLinkXPath]['texts'][$strRelativeTextXPath] = $this->arrNumXPaths[$strTextXPath];
						} else {
							// If text is occuring within 15% as much as links, then include, even though its xpath might be higher than link
							if (abs($intNumOccurs - $this->arrNumXPaths[$strTextXPath]) < ($intNumOccurs / 9)) {
								
								$arrDecomposedTextXPath = explode('/', $strTextXPath);
								$arrDecomposedLinkXPath = explode('/', $strLinkXPath);
								for ($i = 0; $i < count($arrDecomposedLinkXPath); $i++) {
									if ($arrDecomposedTextXPath[$i] != $arrDecomposedLinkXPath[$i]) break;
								}
								$arrSpecificLinkPartsXPath = array_slice($arrDecomposedLinkXPath, $i);
																
								// Do not include text xpaths more than a 3rd the depth of total link xpath away
								if (count($arrSpecificLinkPartsXPath) > (count($arrDecomposedLinkXPath) / 3)) {
									continue;
								}
								

								$strRelativeTextXPath = str_repeat('../', count($arrSpecificLinkPartsXPath));
								$arrSpecificTextPartsXPath = array_slice($arrDecomposedTextXPath, $i);
								$strRelativeTextXPath .= str_replace('#text', 'text()', join('/', $arrSpecificTextPartsXPath));
								$this->arrStructure['links'][$intNumOccurs][$strLinkXPath]['texts'][$strRelativeTextXPath] = $intNumOccurs;
							}
						} 
					}
				}
			}
			
			return $this->arrStructure;
		}			
		
		function getLinks() {
			if ($this->arrLinks) return $this->arrLinks;
			
			$arrStructure = $this->getStructure();
			$arrStructureLinks = $arrStructure['links'];
			
			$arrLinks = array();
			$maxTextLength = 0; $maxCount = 0;
			foreach($arrStructureLinks as $intNumOccurs => $arrLinkXPaths) {
				foreach($arrLinkXPaths as $strLinkXPath => $arrData) {
					$arrPageBlock = array(
						'xpath' => array('expr' => $strLinkXPath, 'occurs' => $intNumOccurs),
						'links' => array(),
						'avg_text_length' => 0
					);
					$linkElements 		= @$this->xpath->query($strLinkXPath);
					$totalTextLength 	= 0;
					if (!is_null($linkElements) && $linkElements) {
		  			foreach ($linkElements as $linkElement) {
		  				$strHref = '';
		  				if (($imageHref = $linkElement->attributes->getNamedItem('href')) &&
		  				    (substr($imageHref->nodeValue, 0, 1) != '#') &&
		  				    (substr($imageHref->nodeValue, 0, 11) != 'javascript:')) {
		  					$strHref = $imageHref->nodeValue;
		  				}
		  				if (!$strHref) continue;
		  				
		  				$arrPageLink = array(
		  					'href' => ($strResolvedHref = $this->wxUrl->resolveRelative($strHref)),
		  					'texts' => array()
		  				);
		  				foreach($arrData as $strItem => $arrItemXPaths) {
		  					switch($strItem) {
		  						case 'texts':
										foreach($arrItemXPaths as $strRelativeTextXPath => $intNumOccurs) {
											$textElements = @$this->xpath->query($strRelativeTextXPath, $linkElement);
											if (!is_null($textElements) && $textElements) {
								  			foreach ($textElements as $textElement) {
								  				$strText = preg_replace("/[\n\r\t ]+/", " ", trim($textElement->nodeValue));
								  				if (strlen($strText) < 1) continue;
								  				$totalTextLength += strlen($strText);
								  				$arrPageText = array(
								  					'xpath' => array('expr' => $strRelativeTextXPath, 'occurs' => $intNumOccurs),
								  					'text' => $strText
								  				);
								  				$arrPageLink['texts'][sprintf("%08d", strlen($arrPageText['xpath']['expr'])) . sprintf("%02d", count($arrPageLink['texts']))] = $arrPageText;
								  			}
								  		}
										}
									default:
								}
							}
							
							ksort($arrPageLink['texts']);
							$arrPageBlock['links'][$arrPageLink['href']] = $arrPageLink;
		  			}
		  		}
		  		
		  		if (count($arrPageBlock['links'])) {
		  			$arrPageBlock['avg_text_length'] = round($totalTextLength / count($arrPageBlock['links']));
		  		}
		  		
		  		$arrLinks[] = $arrPageBlock;
		  		$maxTextLength 	= max($maxTextLength, $arrPageBlock['avg_text_length']);
					$maxCount 			= max($maxCount, 			count($arrPageBlock['links']));
				}
			}
			
			if ($maxTextLength == 0) 	$maxTextLength = 1;
			if ($maxCount == 0) 			$maxCount = 1;
			
			$this->arrLinks = array();
			foreach($arrLinks as $arrPageBlock) {
				$normTextLength	= $arrPageBlock['avg_text_length'] / $maxTextLength;
				$normCount			= count($arrPageBlock['links']) / $maxCount;
				$this->arrLinks[sprintf("%02d", ((2 * $normTextLength) * (0.5 * $normCount)) * 100) . sprintf("%02d", count($this->arrLinks))] = $arrPageBlock;
			}
			
			krsort($this->arrLinks);
			return $this->arrLinks;
		}
		
		function getImages() {
			if ($this->arrImages) return $this->arrImages;
			
			$arrStructure = $this->getStructure();
			$arrStructureImages = $arrStructure['images'];
			
			$arrImages = array();	
			$maxVariance = 0; $maxCount = 0;
			foreach($arrStructureImages as $intNumOccurs => $arrImageXPaths) {
				foreach($arrImageXPaths as $strImageXPath => $arrData) {
					$arrPageBlock = array(
						'xpath' => array('expr' => $strImageXPath, 'occurs' => $intNumOccurs),
						'images' => array(),
						'variance' => 0
					);
					$intPrevPageImageDepth = -1;
					$imageElements = @$this->xpath->query($strImageXPath);
					if (!is_null($imageElements) && $imageElements) {
		  			foreach ($imageElements as $imageElement) {
		  				if ($imageSrc = $imageElement->attributes->getNamedItem('src')) {
		  					$strSrc = trim($imageSrc->nodeValue);
		  				} else if ($imageStyle = $imageElement->attributes->getNamedItem('style')) {
		  					if (preg_match('/background-image: url\(([^\)]+)/', $imageStyle->nodeValue, $arrMatches)) {
		  						$strSrc = trim($arrMatches[1]);
		  					}
		  				}
		  				if (strcmp(substr($strSrc, -4), '.gif') &&
		  						strcmp(substr($strSrc, -4), '.jpg') &&
		  						strcmp(substr($strSrc, -4), '.png')) {
		  					continue;
		  				}
		  				if (!isset($strSrc) || !$strSrc) continue;
		  				
		  				$strHref = '';
		  				if (($imageHref = $imageElement->attributes->getNamedItem('href')) &&
		  				    (substr($imageHref->nodeValue, 0, 1) != '#') &&
		  				    (substr($imageHref->nodeValue, 0, 11) != 'javascript:')) {
		  					$strHref = $imageHref->nodeValue;
		  				} else if (($parentNode = $imageElement->parentNode) &&
		  				    ($parentNodeHref = $parentNode->attributes->getNamedItem('href')) &&
		  				    (substr($parentNodeHref->nodeValue, 0, 1) != '#') &&
		  				    (substr($parentNodeHref->nodeValue, 0, 11) != 'javascript:')) {
		  					$strHref = $parentNodeHref->nodeValue;
		  				}
		  				
		  				$arrPageImage = array(
		  					'src' => ($strResolvedSrc = $this->wxUrl->resolveRelative($strSrc)),
		  					'href' => $this->wxUrl->resolveRelative($strHref),
		  				);
		  				$arrPageBlock['images'][$arrPageImage['src']]	= $arrPageImage;
							
							$intDepth = substr_count($strResolvedSrc, '/') + substr_count($strResolvedSrc, '?') + substr_count($strResolvedSrc, '#');
							if ($intPrevPageImageDepth >= 0) {
								$arrPageBlock['variance'] += abs($intPrevPageImageDepth - $intDepth);
							}
							$intPrevPageImageDepth = $intDepth;
		  			}
		  		} 
		  		
		  		$arrImages[] = $arrPageBlock;
		  		$maxVariance 	= max($maxVariance, $arrPageBlock['variance']);
					$maxCount 		= max($maxCount, 		count($arrPageBlock['images']));
				}
			}
			
			if ($maxVariance == 0) 	$maxVariance = 1;
			if ($maxCount == 0) 		$maxCount = 1;
			
			$this->arrImages = array();
			foreach($arrImages as $arrPageBlock) {
				$normVariance = 1 - ($arrPageBlock['variance'] / $maxVariance);
				if ($normVariance == 0) $normVariance = 0.1;
				$normCount		= count($arrPageBlock['images']) / $maxCount;
				$this->arrImages[sprintf("%02d", ((2 * $normCount) * (0.5 * $normVariance)) * 100) . sprintf("%02d", count($this->arrImages))] = $arrPageBlock;
			}
			
			krsort($this->arrImages);
			return $this->arrImages;
		}
		
		function getPaginator() {
			$arrLinks = array();
			
			$linkElements = @$this->xpath->query('//a');
			if (!is_null($linkElements) && $linkElements) {
  			foreach ($linkElements as $linkElement) {
  				if (($linkNodeHref = $linkElement->attributes->getNamedItem('href')) &&
		  	      ($strHref = $linkNodeHref->nodeValue)) {
	  				if (substr($strHref, 0, 1) == '#') continue;
	  				if (substr($strHref, 0, 11) == 'javascript:') continue;
	  				if (($i = strpos($strHref, '#')) !== false) {
	  					$strHref = substr($strHref, 0, $i);
	  				}
	  	
	  				$arrPath = explode('/', html_entity_decode(parse_url($strHref, PHP_URL_PATH)));
						// WebXtractor_Logger::debug('HTML PARSER: PAGINATOR: ' . join('*', $arrPath));
				
						if (($strLastPathItem = array_pop($arrPath)) ||
								($strLastPathItem = array_pop($arrPath))) {
	  					if (($idx = strrpos($strLastPathItem, '.')) !== false) {
	  						$strLastPathItem = substr($strLastPathItem, 0, $idx);
	  						$arrPath = explode('-', $strLastPathItem);
	  						$strLastPathItem = array_pop($arrPath);
	  					}
	  					
	  					$intPageNum = 0 + join(array_diff(str_split($strLastPathItem), str_split(preg_replace("/[0-9]+/", "", $strLastPathItem))));
	  					WebXtractor_Logger::debug('HTML PARSER: PAGINATOR: ' . $strLastPathItem . ' HAS PAGENUM ' . $intPageNum);
	  					
	  					if ($intPageNum) {
	  						$strStrippedHref = str_replace($intPageNum, '', $strHref);
	  						$arrLinks[$strStrippedHref][$strHref] = array(
			  					'href' => $this->wxUrl->resolveRelative($strHref),
			  					'pagenum' => $intPageNum
			  				);
	  					}
	  				} 
	  				
	  				$arrQuery = explode('&',html_entity_decode(parse_url($strHref, PHP_URL_QUERY)));
	  				foreach($arrQuery as $strQueryParam) {
	  					$arrParts = explode('=', $strQueryParam);
	  					if (isset($arrParts[1])) {
		  					$intPageNum = 0 + join(array_diff(str_split($arrParts[1]), str_split(preg_replace("/[0-9]+/", "", $arrParts[1]))));
		  					if ($intPageNum) {
		  						$strStrippedHref = str_replace($strQueryParam, '', $strHref);
		  						$arrLinks[$strStrippedHref][$strHref] = array(
				  					'href' => $this->wxUrl->resolveRelative($strHref),
				  					'pagenum' => $intPageNum
				  				);
		  					}
		  				}
	  				}
	  			}
  			}
  		}
			 
			$arrPaginator 			= array();
  		$arrMinAmplitude 		= array('amplitude' => 99999, 'paginatorKey' => '');
  		$arrMinFluctuation	= array('fluctuation' => 99999, 'paginatorKey' => '');
			
			foreach($arrLinks as $strStrippedHref => $arrHrefs) {
				if (count($arrHrefs) < self::PAGINATION_MIN_LINK_OCCURS) continue;
				
				$arrPaginator[$strStrippedHref] = array();
				$arrPageNums = array();
				
				foreach($arrHrefs as $strHref => $arrPaginatorLink) {
  				$arrPaginator[$strStrippedHref][] = $arrPaginatorLink;
  				$arrPageNums[] = $arrPaginatorLink['pagenum'];
  			}
  		
  			asort($arrPageNums);
  			$intAmplitude = 0;
  			$intPrevFluctuation = 0;
  			$intFluctuation = 0;
  			for ($i = 1; $i < count($arrPageNums); $i++) {
	  			$intAmplitude += abs((0 + $arrPageNums[$i]) - (0 + $arrPageNums[$i - 1]));
	  			$intFluctuation += abs(abs((0 + $arrPageNums[$i]) - (0 + $arrPageNums[$i - 1])) - $intPrevFluctuation);
	  			$intPrevFluctuation = abs((0 + $arrPageNums[$i]) - (0 + $arrPageNums[$i - 1]));
	  		}
  			
	  		$intAmplitude /= (count($arrPageNums) - 1); // precond: PAGINATION_MIN_LINK_OCCURS > 1
	  		if ($intAmplitude < $arrMinAmplitude['amplitude']) {
	  			$arrMinAmplitude = array(
	  				'amplitude' 		=> $intAmplitude,
	  				'paginatorKey' 	=> $strStrippedHref
	  			);
	  		}

				if ($intFluctuation < $arrMinFluctuation['fluctuation']) {
	  			$arrMinFluctuation = array(
	  				'fluctuation' 	=> $intFluctuation,
	  				'paginatorKey' 	=> $strStrippedHref
	  			);
	  		}
  		}
  		
  		return ($arrMinFluctuation['paginatorKey']) ? $arrPaginator[$arrMinFluctuation['paginatorKey']] : null;
		}
	}
?>