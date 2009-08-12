<?php

class String {

	function Lowercase($string) {
		$lowercaseMap = array(
			'Á' => 'á',
			'À' => 'à',
			'Â' => 'â',
			'Ä' => 'ä',

			'É' => 'é',
			'È' => 'è',
			'Ê' => 'ê',
			'Ë' => 'ë',
			
			'Í' => 'í',
			'Ì' => 'ì',
			'Î' => 'î',
			'Ï' => 'ï',
			
			'Ó' => 'ó',
			'Ò' => 'ò',
			'Ô' => 'ô',
			'Ö' => 'ö',
			
			'Ú' => 'ú',
			'Ù' => 'ù',
			'Û' => 'û',
			'Ü' => 'ü',

			'Ç' => 'ç',
			'Ñ' => 'ñ',
			'Œ' => 'œ',
			'Æ' => 'æ'
		);
		foreach ($lowercaseMap as $uppercase => $lowercase) {
			$match[] = "'". $uppercase ."'u";
			$replace[] = $lowercase;
		}
		return preg_replace($match, $replace, strtolower($string));
	}
	
	function ToLowerASCII($string) {
		// Simplify ligatures and dumbize some characters
		$match = array("'œ'u","'æ'u","'ﬂ'u","'ﬁ'u","'’'u","'[“”«»]'u");
		$replace = array('oe','ae','fl','fi','\'','"');
		$simplifiedString = preg_replace($match, $replace, $string);
		
		$match = array(
			"'[áàâä]'u",
			"'[ÁÀÂÄ]'u",
			"'[éèêë]'u",
			"'[ÉÈÊË]'u",
			"'[íìîï]'u",
			"'[ÍÌÎÏ]'u",
			"'[óòôö]'u",
			"'[ÓÒÔÖ]'u",
			"'[úùûü]'u",
			"'[ÚÙÛÜ]'u",
			"'ç'u",
			"'Ç'u",
			"'ñ'u",
			"'Ñ'u",
			"'". chr(204) .".'" // Not extensively tested: fix for MB in uploaded filenames
		);
		$replace = array('a','A','e','E','i','I','o','O','u','U','c','C','n','N','');
		$unaccentedString = preg_replace($match, $replace, $simplifiedString);
		
		return $unaccentedString;
	}
	
	function PrepareForURL($string) {
		// Make lowercase
		$lowercaseString = String::Lowercase($string);

		// Make lower ASCII
		$lowerASCIIString = String::ToLowerASCII($lowercaseString);
		
		// Only keep the first six words to avoid long URLs
		$wordsArray = explode(' ', $lowerASCIIString);
		$trimmedString = implode(' ', array_slice($wordsArray, 0, 7));
				
		// Only keep 'safe' characters and turn spaces into underscores
		$match = array('/\s/','/[^a-z\d-_\+]/','/_+/','/\.+$/');
		$replace = array('_','','_','');
		$safeString = preg_replace($match, $replace, $trimmedString);
		
		// Check if safeString is empty before returning
		return ($safeString == '' ? '-' : $safeString);
	}

	function IsURL($url) {
		return (strpos($url, 'http://') === 0);
	}
	
	function LocalizeNumber($number, $decimals = 1) {
		global $_JAM;
		switch ($_JAM->language) {
			case 'fr':
				return number_format($number, $decimals, ',', '&nbsp;');
				break;
			default:
				return number_format($number, $decimals, '.', ',');
				break;
		}
	}
		
	function BytesToString($bytes) {
		global $_JAM;
		$strings = $_JAM->strings['filesizeUnits'];
		
		if ($bytes == 0) {
			return '0 '. $strings['kb'];
		} elseif ($bytes < pow(1024, 2)) {
			return String::LocalizeNumber($bytes / 1024, 0) .' '. $strings['kb'];
		} elseif ($bytes < pow(1024, 3)) {
			return String::LocalizeNumber($bytes / pow(1024, 2)) .' '. $strings['mb'];
		} elseif ($bytes < pow(1024, 4)) {
			return String::LocalizeNumber($bytes / pow(1024, 3)) .' '. $strings['gb'];
		} elseif ($bytes < pow(1024, 5)) {
			return String::LocalizeNumber($bytes / pow(1024, 4)) .' '. $strings['tb'];
		} elseif ($bytes < pow(1024, 6)) {
			return String::LocalizeNumber($bytes / pow(1024, 5)) .' '. $strings['pb'];
		} else {
			return false;
		}
	}

}


?>
