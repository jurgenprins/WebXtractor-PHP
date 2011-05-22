<?
	define('WEBXTRACTOR_LIBRARY_PATH', dirname(__FILE__));
	
	spl_autoload_register(function ($className) {
		$strPath = WEBXTRACTOR_LIBRARY_PATH . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, array_slice(explode('_', $className), 1)) . '.php';
		if (file_exists($strPath)) {
       require_once($strPath);
       return true;
    }
    return false;
	}); 
?>