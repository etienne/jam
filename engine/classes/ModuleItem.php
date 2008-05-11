<?php

class ModuleItem {
	
	var $data;
	
	/*
	 * Construtor
	 */
	
	function ModuleItem(&$module, $id, $data) {
		// Find path for this item, if available
		$this->path = @$this->paths[$this->itemID];
		
	}
	
}

?>