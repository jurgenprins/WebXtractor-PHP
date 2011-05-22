<?
	class WebXtractor_Extractor_Object {
		private $wxSrcUrl	= null;
 		private $strLink	= null;
 		private $strTitle = null;
 		private $strImage = null;
 		private $strText 	= null;
 		
 		function __construct(WebXtractor_Net_Url $wxSrcUrl, $strLink, $strTitle = '', $strImage = '', $strText = '') {
 			$this->wxSrcUrl		= $wxSrcUrl;
 			$this->strLink		= $strLink;
 			$this->strTitle		= $strTitle;
 			$this->strImage		= $strImage;
 			$this->strText		= $strText;
 		}
 			
 		function getSourceUrl()	{ return $this->wxSrcUrl; }
 		function getLink()			{ return $this->strLink; }
 		function getTitle()			{ return $this->strTitle; }
 		function getImage()			{ return $this->strImage; }
 		function getText()			{ return $this->strText; }
 		function hashCode()			{ return md5($this->strLink . $this->strImage); }
 	}
?>