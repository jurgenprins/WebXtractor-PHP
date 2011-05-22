<?
	class WebXtractor_Extractor_Meta {
		private $wxSrcUrl	= null;
 		private $strTitle = null;
 		
 		function __construct(WebXtractor_Net_Url $wxSrcUrl, $strTitle = '') {
 			$this->wxSrcUrl		= $wxSrcUrl;
 			$this->strTitle		= $strTitle;
 		}
 			
 		function getSourceUrl()	{ return $this->wxSrcUrl; }
 		function getTitle()			{ return $this->strTitle; }
 	}
?>