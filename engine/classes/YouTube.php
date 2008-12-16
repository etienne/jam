<?php

class YouTube {
	
	function GetPlayerFromURL($url, $id, $width, $height, $color = '#000000') {
		// URL is in format http://www.youtube.com/watch?v=ACUnPfVuBZg
		$movieID = ltrim(strstr($url, '='), '=');
		return HTML::SWFObject('http://www.youtube.com/v/'. $movieID, $id, $width, $height, 8, $color);
	}
	
}

?>