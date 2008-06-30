<?php

class Cookie {
	
	function Create($name, $value, $expiry = 2592000) {
		// Default expiry is 30 days (30 * 24 * 60 * 60)
		setcookie($name, $value, time() + $expiry, ROOT);
		return true;
	}
	
	function Delete($name) {
		setcookie($name, '', 0, ROOT);
		return true;
	}
	
}
