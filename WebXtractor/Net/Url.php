<?
	class WebXtractor_Net_Url {
		private $strUrl = null;
		private $strDomain = null;
		private $arrParts = null;
		
		function __construct($strUrl) {
			$this->strUrl = strstr($strUrl, '://') ? $strUrl : ('http://' . $strUrl);
			$this->arrParts = parse_url($this->strUrl);
		}
		
		function getUrl() {
			return $this->strUrl;
		}
		
		function getParts() {
			return $this->arrParts;
		}
		
		function getDomain() {
			if ($this->strDomain) return $this->strDomain;
			return ($this->strDomain = join('.', array_slice(explode('.', $this->arrParts['host']), -2)));
		}
		
		function resolveRelative($strRelativeUrl) {
	    $p = @parse_url($strRelativeUrl);
	    if(isset($p["scheme"]) && $p["scheme"]) return $strRelativeUrl;
	    
	    extract($this->arrParts);
	    if(!isset($path)) return $strRelativeUrl;
	    $path = str_replace('\\','/',dirname($path)); 
	
			if($strRelativeUrl && $strRelativeUrl{0} == '/') {
	        $cparts = array_filter(explode("/", $strRelativeUrl));
	    } else if ($strRelativeUrl && $strRelativeUrl{0} == '?') {
	    		$cparts = array_filter(explode("/", str_replace('\\', '/', $this->arrParts['path'] . $strRelativeUrl)));
	    }
	    else {
	        $aparts = array_filter(explode("/", $path));
	        $rparts = array_filter(explode("/", $strRelativeUrl));
	        $cparts = array_merge($aparts, $rparts);
	        
	        foreach($cparts as $i => $part) {
	            if($part == '.') {
	                $cparts[$i] = null;
	            }
	            if($part == '..') {
	            		for ($j = $i - 1; $j >= 0; $j--) {
	            			if ($cparts[$j] != null) {
	            				$cparts[$j] = null;
	            				break;
	            			}
	            		}
	                $cparts[$i] = null;
	            }
	        }
	        $cparts = array_filter($cparts);
	    }
	    $path = implode("/", $cparts);
	    
	    $url = "";
	    if($scheme) {
	        $url = "$scheme://";
	    }
	    if(isset($user) && $user) {
	        $url .= "$user";
	        if($pass) {
	            $url .= ":$pass";
	        }
	        $url .= "@";
	    }
	    if(isset($host) && $host) {
	        $url .= "$host/";
	    }
	    $url .= $path;

			return $url;
	  }
	  
	}
?>
	