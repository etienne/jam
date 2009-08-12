<?php

class HTTP {

	function Redirect($url) {
		header('Location: ' . $url);
		exit;
		return true;
	}
	
	function ReloadCurrentURL($suffix) {
		global $_JAM;
		$url = ROOT . $_JAM->request . $suffix;
		header('Location: ' . $url);
		exit;
		return true;
	}
	
	function RedirectLocal($url) {
		header('Location: ' . ROOT . $url);
		exit;
		return true;
	}
	
	function NewLocation($url) {
		header("HTTP/1.0 301 Moved permanently");
		header('Location: ' . ROOT . $url);
		exit;
		return true;
	}
	
}

?>
