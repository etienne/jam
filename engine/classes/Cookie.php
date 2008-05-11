<?php

class Cookie {
	
	function Create($name, $value, $expiry = 2592000) {
		// Default expiry is 30 days (30 * 24 * 60 * 60)
		global $_JAG;
		setcookie($name, $value, time() + $expiry, ROOT);
		return true;
	}
	
	function Delete($name) {
		global $_JAG;
		setcookie($name, '', 0, ROOT);
		return true;
	}
	
}
