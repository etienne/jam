<?php

class Vimeo {
	
	function GetPlayerFromURL($url, $id, $width, $height, $color = '#000000') {
		// URL is in format http://www.vimeo.com/1473997
		$movieID = ltrim(strrchr($url, '/'), '/');
		return HTML::SWFObject('http://vimeo.com/moogaloop.swf?clip_id='. $movieID .'&amp;server=vimeo.com&amp;fullscreen=1&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=333333', $id, $width, $height, 8, $color);
	}
	
}

?>
