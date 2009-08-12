<?php

class Cache {
	
	var $cacheFile;
	var $forbid;
	
	function Cache() {
		// Determine cache file path
		$hash = md5($_SERVER['REDIRECT_URL']);
		$this->cacheFile = 'cache/'. $hash;
		
		// Start output buffering
		ob_start('mb_output_handler');

		// Look for cached version of this page
		if (file_exists($this->cacheFile) && !$_POST && !$_GET) {
			// For now we only support the text/html mime type
			header('Content-type: text/html');
			
			// Include cached file and exit
			include($this->cacheFile);
			exit();
		}
	}
	
	function Clear() {
		// This deletes everything in the cache directory; brutal but will do for now
		if ($cacheFiles = Filesystem::GetFilenames('cache')) {
			foreach ($cacheFiles as $file) {
				if (!unlink('cache/'. $file)) {
					trigger_error("Couldn't delete cache file ". $file, E_USER_ERROR);
					return false;
				}
			}
		}
		return true;
	}
	
	function Forbid() {
		$this->forbid = true;
	}
	
	function Write() {
		global $_JAM;
		// Only cache HTML content intended for anonymous users
		if (!$_JAM->user->IsLoggedIn() && !$this->forbid && $_JAM->contentType == 'text/html' && !$_POST && !$_GET) {
			// Get contents of output buffer
			$string = ob_get_contents();
			
			// Write to file
			if ($handle = @fopen($this->cacheFile, 'w')) {
				fwrite($handle, $string);
			}
		}
		ob_end_flush();
	}
	
}

?>
