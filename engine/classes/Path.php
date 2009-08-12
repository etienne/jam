<?php

require_once('classes/Database.php');
require_once('classes/String.php');

class Path {

	function Insert($path, $module, $item, $safeInsert = true, $language = false) {
		global $_JAM;
		
		// Use default language if none is provided
		if (!$language) {
			$language = $_JAM->defaultLanguage;
		}
		
		// Disable all paths that represent exactly the same resource
		$params = array('current' => false);
		$where = array(
			'module = '. (int)$module,
			'item = '. (int)$item,
			"language = '". $language ."'"
		);
		if (!Database::Update('_paths', $params, $where)) {
			trigger_error("Couldn't disable other paths representing the same resource", E_USER_WARNING);
			return false;
		}
		
		// Convert to lower ASCII
		$path = String::ToLowerASCII($path);
		
		// Replace spaces with %20
		$path = str_replace(' ', '%20', $path);
		
		// Check whether path already exists
		if ($duplicate = $_JAM->paths[$path]) {
			// Path already exists
			if ($duplicate['module'] == $module && $duplicate['item'] == $item && $duplicate['language'] == $language) {
				// This path represents the same resource; enable it and we're done
				$params = array('current' => true);
				if (Database::Update('_paths', $params, 'id = '. $duplicate['id'])) {
					return $path;
				} else {
					return false;
				}
			} else {
				// This path represents another resource
				if ($safeInsert) {
					// We don't want to overwrite the existing path; find the next unique path
					$basePath = $path;
					$i = 1;
					while ($_JAM->paths[$path] && $i++ < 999) {
						$path = $basePath .'_'. $i;
					}
				} else {
					// We want to force this URL by overwriting the duplicate path
					if (Database::Update('_paths', $params, 'path = '. $path)) {
						// That's it, we're done
						return $path;
					} else {
						trigger_error("Couldn't overwrite duplicate path", E_USER_ERROR);
					}
				}
			}
		}
		
		// Insert path
		$params = array(
			'path' => $path,
			'current' => true,
			'module' => $module,
			'item' => $item,
			'language' => $language
		);
		
		if (Database::Insert('_paths', $params)) {
			return $path;
		} else {
			trigger_error("Couldn't insert path into database");
			return false;
		}

	}
	
	function DeleteAll($module, $item) {
		// Delete all paths for a specific item
		$where = array(
			'module = '. $module,
			'item = '. $item
		);
		if (Database::DeleteFrom('_paths', $where)) {
			return true;
		} else {
			trigger_error("Couldn't delete paths", E_USER_WARNING);
		}
	}

}

?>
