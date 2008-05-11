<?php

require_once('classes/Form.php');

class FormGroup extends Form {
	
	function FormGroup($parentForm) {
		$this->values = $parentForm->values;
	}
	
	function GetString() {
		// Get rid of whitespace between tags
		$cleanString = preg_replace('/\s</', '<', $this->string);
		return $cleanString;
	}
	
}

?>
