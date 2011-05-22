<?
	class WebXtractor_Util_Matcher {
 		private static $instance;
    
		protected $stopwords = null;
 		
 		private function __construct($lang = 'nl') {
 			$this->stopwords = $this->splitString(dirname(__FILE__) . 'Matcher' . DIRECTORY_SEPARATOR . 'stopwords.' . $lang . '.txt');
 		}
 		private function __clone() {}

		public static function getInstance($lang = 'nl') {
        if (!self::$instance[$lang] instanceof self) {
            self::$instance[$lang] = new self($lang);
        }
        return self::$instance[$lang];
    }

		public function splitString($str) {
			return preg_split('/[^A-Za-z0-9_\-.]+/', $str);
		}
		
		public function compile($str, $mixedStopWords = '') {
			if (!$str)				return array();
			
			if (is_array($mixedStopWords)) {
				$aStopWords 	= array_merge($mixedStopWords, $this->stopwords);
			} else {
				if (strlen($mixedStopWords)) {
					$aStopWords 	= array_merge($this->splitString(strtolower($mixedStopWords)), $this->stopwords);
				} else {
					$aStopWords		= $this->stopwords;
				}
			}
			
			$aPostedWords = $this->splitString($str);
			
			$aWords = array();
			foreach($aPostedWords as $strWord) {
				$strWord = trim(strtolower($strWord));
				if (in_array($strWord, $aStopWords)) {
					continue;
				}
				if ((strlen($strWord) < 2) && ((0 + $strWord) == 0)) {
					continue;
				}
				$aWords[$strWord] = true;
			}
			
			return array_keys($aWords);
		}
		
		public function match(array $aSource, array $aTarget, $fltMinDistanceRatio = 0.3) {
			$nTargetWords	= count($aTarget);
			
			$nMatchedWords			= 0;
			$nScore							= 0;
			foreach($aTarget as $sTargetWord) {
				foreach($aSource as $sSourceWord) {
					if (!$sTargetWord || !$sSourceWord) continue;
					if (!strcmp($sTargetWord, $sSourceWord)) {
						$nMatchedWords++;
						continue;
					}
					$nDistance	= levenshtein($sTargetWord, $sSourceWord);
					$nLen				= max(strlen($sTargetWord), strlen($sSourceWord));
					if (($nDistance / $nLen) <= $fltMinDistanceRatio) {
						$nMatchedWords++;
					}
				}
			}
			$nScore	= round(($nMatchedWords / $nTargetWords) * 100);
			return $nScore;
		}
		
		public function matchStrings($strSource, $arrTargets, $fltMinDistanceRatio = 0.3, $minMatchScore = 75) {
			$mSrc = $this->compile($strSource);
			if (is_array($arrTargets) && count($arrTargets)) {
				$aItemByScore = array();
				foreach($arrTargets as $strTarget) {
					$score = $this->match(
						$mSrc, 
						$this->compile($strTarget),
						$fltMinDistanceRatio);
					
					if ($score > $minMatchScore) {
						$aItemByScore[$score . '-' . substr($strTarget, 0, 10)] = $strTarget;
					}
				}
				
				if (0 == count($aItemByScore)) {
					return null;
				}
				
				arsort($aItemByScore);
				$aBestItems = array();
				foreach($aItemByScore as $score => $strTarget) {
					if (($i = strpos($score, '-')) > 0) {
						$score = 0 + substr($score, 0, $i);
						if (!isset($highScore)) $highScore = $score;
						if ($score < $highScore) break;
						array_push($aBestItems, $strTarget);
						$highScore = $score;
					}
				}

				switch(count($aBestItems)) {
					case 0:	return null;
					case 1: return array('score' => $highScore, 'item' => $aBestItems[0]);
					default: 
						$aItemByScore = array();
						foreach($aBestItems as $strTarget) {
							$aItemByScore[levenshtein($strSource, $strTarget)] = $strTarget;
						}
						asort($aItemByScore);
						foreach($aItemByScore as $strTarget) {
							return array('score' => $highScore, 'item' => $strTarget);
						}
				}
					
				return null;
			}
		}
	}
?>