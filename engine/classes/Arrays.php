<?php

class Arrays {

	function GetListString ($array, $or = false) {
		global $_JAG;
		
		$length = count($array);
		$i = 1;
		foreach ($array as $item) {
			$output .= $item;
			switch ($length - $i++) {
				case 0:
					$output .= '';
					break;
				case 1:
					$output .= ' '. ($or ? $_JAG['strings']['words']['or'] : $_JAG['strings']['words']['and']) .' ';
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
	
}

?>
