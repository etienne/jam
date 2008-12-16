<?php

class Filesystem {
	
	/*
	 * Static
	 */

	function FileExistsInIncludePath($file) {
		// Taken from http://aidanlister.com/repos/v/function.file_exists_incpath.php
		$paths = explode(PATH_SEPARATOR, get_include_path());
	
		foreach ($paths as $path) {
			// Formulate the absolute path
			$fullpath = $path . DIRECTORY_SEPARATOR . $file;
	
			// Check it
			if (file_exists($fullpath)) {
				return $fullpath;
			}
		}

		return false;
	}

	function GetFilenames($dir) {
		// Abort if directory doesn't exist
		if (!file_exists($dir)) return false;
		
		// Return an array with both the key and the value as the filename
		$dir = rtrim($dir, '/');
		$dirHandle = opendir($dir);
		while ($filename = readdir($dirHandle)) {
			if (!is_dir($dir . "/" . $filename)) {
				$result[$filename] = $filename;
			}
		}
		return $result;
	}

	function GetDirNames($dir) {
		// Abort if directory doesn't exist
		if (!file_exists($dir)) return false;

		$dir = rtrim($dir, '/');
		$dirHandle = opendir($dir);
		while ($dirname = readdir($dirHandle)) {
			if (is_dir($dir . "/" . $dirname)) {
				if (substr($dirname, 0, 1) != '.') {
					$result[$dirname] = $dirname;
				}
			}
		}
		return $result;
	}
	
}

?>
