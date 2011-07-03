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
 			$this->strTitle		= WebXtractor_Extractor_Object::safeString($strTitle);
 			$this->strImage		= $strImage;
 			$this->strText		= WebXtractor_Extractor_Object::safeString($strText);
 		}
 			
 		function getSourceUrl()	{ return $this->wxSrcUrl; }
 		function getLink()			{ return $this->strLink; }
 		function getTitle()			{ return $this->strTitle; }
 		function getImage()			{ return $this->strImage; }
 		function getText()			{ return $this->strText; }
 		function hashCode()			{ return md5($this->strLink . $this->strImage); }
 		
 		static function safeString($string) { 
 		  return trim(preg_replace('/[^0-9a-zA-Z]+/i', ' ', $string));
		}
	}
?>