<?php

class IniFile {
	
	/*
	 * Static
	 */
	
	function Parse ($iniFile, $parseSections = false) {
		if (file_exists($iniFile)) {
			$parsedArray = parse_ini_file($iniFile, $parseSections);
			foreach ($parsedArray as $key => $parsedItem) {
				if (is_string($parsedItem)) {
					if (preg_match('/^\[([^\[]+)\]$/', $parsedItem, $matchesArray)) {
						$parsedArray[$key] = explode(', ', $matchesArray[1]);
					}
				}
			}
			return $parsedArray;
		} else {
			// Return false if the file doesn't exist
			return false;
		}
	}
	
}

?>