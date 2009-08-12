<?php

class ErrorsModule extends Module {
	
	function DefaultViewController() {
		global $_JAM;
		
		$_JAM->title = $this->strings['title'];
	}
	
}

?>