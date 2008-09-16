<?php

class IniFile {
	
	/*
	 * Static
	 */
	
	function ProcessArrays ($array) {
		foreach ($array as $key => $parsedItem) {
			if (is_string($parsedItem)) {
				if (preg_match('/^<([^>]+)>$/', $parsedItem, $matchesArray)) {
					$returnArray[$key] = explode(', ', $matchesArray[1]);
				} else {
					$returnArray[$key] = $parsedItem;
				}
			} elseif (is_array($parsedItem)) {
				$returnArray[$key] = IniFile::ProcessArrays($parsedItem);
			}
		}
		return $returnArray;
	}
	
	function Parse ($iniFile, $parseSections = false) {
		if (file_exists($iniFile)) {
			$parsedArray = parse_ini_file($iniFile, $parseSections);
			return IniFile::ProcessArrays($parsedArray);
		} else {
			// Return false if the file doesn't exist
			return false;
		}
	}
	
}

?>