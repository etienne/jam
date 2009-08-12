<?php

require_once('classes/Template.php');

class Layout extends Template {
	
	function SetLayout($name) {
		// Make sure requested layout exists
		$requestedLayout = 'layouts/'. $name .'.php';
		if (Filesystem::FileExistsInIncludePath($requestedLayout)) {
			$this->templateFile = $requestedLayout;
		} else {
			return false;
		}
	}
	
}

?>