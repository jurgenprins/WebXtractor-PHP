<?
	class WebXtractor_Net_UrlReader {
		private $strReferer 		= 'http://www.google.com';
		private $strAgent 			= 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)';
		private $strCookieFile 	= null;
		private $intTimeout 		= 60;
		
		function __construct($strCookieFile = null) {
			$this->strCookieFile = $strCookieFile;
		}
		
		function getCurl() {
			$c = curl_init();
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($c, CURLOPT_NOSIGNAL, 1);
			curl_setopt($c, CURLOPT_NOPROGRESS, 1);
			curl_setopt($c, CURLOPT_FAILONERROR, 1);
			curl_setopt($c, CURLOPT_HEADER, false);
			curl_setopt($c, CURLOPT_TIMEOUT, $this->intTimeout);
			curl_setopt($c, CURLOPT_USERAGENT, $this->strAgent);
			if (!is_null($this->strCookieFile)) {
				curl_setopt($c, CURLOPT_COOKIEFILE, $this->strCookieFile);
				curl_setopt($c, CURLOPT_COOKIEJAR, $this->strCookieFile);
			}
			return $c;
		}
		
		function setReferer($strReferer) {
			$this->strReferer = $strReferer;
		}
		
		function fetch($mixedWxUrl, $cbSuccess, $cbFail) {
			$mh = curl_multi_init();
			$arrCurlThreads = array();
	
			if (!is_array($mixedWxUrl)) $mixedWxUrl = array($mixedWxUrl);
			
			foreach ($mixedWxUrl as $wxUrl) {
				WebXtractor_Logger::debug('URL READER..: START ' . $wxUrl->getUrl());
 			
 				$strUrl = $wxUrl->getUrl();
 				
 				if (substr($strUrl, 0, 4) == 'file') {
 					if (($data = @file_get_contents($strUrl)) !== false) {
 						WebXtractor_Logger::debug('FILE READER..: OK ' . $wxUrl->getUrl());
				    $cbSuccess($wxUrl, $data);
				    WebXtractor_Logger::debug('FILE READER..: SUCCESS CALLBACK FINISHED ' . $wxUrl->getUrl());
				  } else {
				  	WebXtractor_Logger::debug('FILE READER..: FAIL ' . $wxUrl->getUrl());
				  	$cbFail($wxUrl, 'failed to read file');
				  	WebXtractor_Logger::debug('FILE READER..: FAIL CALLBACK FINISHED ' . $wxUrl->getUrl());
				  }
 					continue;
 				}
 				
			  $arrCurlThreads[$strUrl] = $this->getCurl();
				curl_setopt($arrCurlThreads[$strUrl], CURLOPT_REFERER, $this->strReferer);
			  curl_setopt($arrCurlThreads[$strUrl], CURLOPT_URL, $strUrl);
			  curl_multi_add_handle ($mh, $arrCurlThreads[$strUrl]);
			}
	
			do {
			  $mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	
			while ($active and $mrc == CURLM_OK) {
			  if (curl_multi_select($mh) != -1) {
			    do {
			      $mrc = curl_multi_exec($mh, $active);
			    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
			  }
			}
	
			if ($mrc != CURLM_OK) {
			  // curl multi read error $mrc
			}
	
			foreach ($mixedWxUrl as $wxUrl) {
				$strUrl = $wxUrl->getUrl();
			  if (($err = @curl_error($arrCurlThreads[$strUrl])) == '') {
		  		WebXtractor_Logger::debug('URL READER..: OK ' . $wxUrl->getUrl());
			    $cbSuccess($wxUrl, @curl_multi_getcontent($arrCurlThreads[$strUrl]));
			    WebXtractor_Logger::debug('URL READER..: SUCCESS CALLBACK FINISHED ' . $wxUrl->getUrl());
			  } else {
			  	WebXtractor_Logger::debug('URL READER..: FAIL ' . $wxUrl->getUrl());
			  	$cbFail($wxUrl, $err);
			  	WebXtractor_Logger::debug('URL READER..: FAIL CALLBACK FINISHED ' . $wxUrl->getUrl());
			  }
			  @curl_multi_remove_handle($mh, $arrCurlThreads[$strUrl]);
			  @curl_close($arrCurlThreads[$strUrl]);
			  WebXtractor_Logger::debug('URL READER..: CLOSED ' . $strUrl . ' OF ' . count($mixedWxUrl));
			}
			curl_multi_close($mh);
			
			return count($mixedWxUrl);
		}
	}
?>