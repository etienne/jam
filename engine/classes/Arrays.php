<?php

class Arrays {

	function GetListString ($array, $or = false) {
		global $_JAM;
		
		$length = count($array);
		$i = 1;
		foreach ($array as $item) {
			$output .= $item;
			switch ($length - $i++) {
				case 0:
					$output .= '';
					break;
				case 1:
					$output .= ' '. ($or ? $_JAM->strings['words']['or'] : $_JAM->strings['words']['and']) .' ';
					break;
				default:
					$output .= ', ';
					break;
			}
		}
		return $output;
	}
	
	function GetDisplayString ($array) {
		return '<pre>' . print_r($array, true) . '</pre>';
	}
	
	function Add (&$array, $items) {
		// Add an item to an array; if it is an array, merge both arrays; else add the passed item to the array
		if (is_array($items)) {
			$array = $array + $items;
		} else {
			$array[] = $items;
		}
	}
	
	function Display($array) {
		print Arrays::GetDisplayString($array);
	}
	
	function GetAdjacentKeys($array, $currentKey, $loop = true) {
		// Given $currentKey, returns previous and next keys in $array
		
		// Loop through each item in array, looking for $currentKey
		$i = 1;
		foreach ($array as $key => $item) {
			// Identify first key
			if (!isset($firstKey)) {
				$firstKey = $key;
			}
			
			if ($flagNextKey) {
				$nextKey = $key;
				$flagNextKey = false;
			}
			if ($currentKey == $key) {
				$currentIndex = $i;
				$previousKey = $previousLoopKey;
				$flagNextKey = true;
			}
			$previousLoopKey = $key;
			$lastKey = $key;
			$i++;
		}
		
		if ($loop) {
			if (!isset($previousKey)) {
				// Previous key is last key
				$previousKey = $lastKey;
			}
			if (!isset($nextKey)) {
				// Next key is first key
				$nextKey = $firstKey;
			}
		}
		
		$totalCount = count($array);
		
		return array($previousKey, $nextKey, $currentIndex, $totalCount);
	}
	
}

?>
