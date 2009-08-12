<?php

class Template {
	
	var $variables;
	var $templateFile;
	
	function Template($name = '') {
		if ($name) {
			$this->SetTemplate($name);
		}
	}
	
	function SetTemplate($name) {
		// Make sure requested template exists
		$requestedTemplate = 'templates/'. $name .'.php';
		if (Filesystem::FileExistsInIncludePath($requestedTemplate)) {
			$this->templateFile = $requestedTemplate;
		} else {
			return false;
		}
	}
	
	function AddVariable($name, $value) {
		$this->variables[$name] = $value;
	}
	
	function AddVariables($array) {
		foreach ($array as $key => $value) {
			$this->AddVariable($key, $value);
		}
	}
	
	function GetOutput($body) {
		global $_JAM;
		
		// Start output buffering
		ob_start('mb_output_handler');
		
		// Make sure we have a valid template file
		if ($this->templateFile) {
			// If 'body' variable is not set, use $body
			if (!$this->variables['body']) {
				$this->variables['body'] = $body;
			}

			// Load $this->variables into local symbol table
			extract($this->variables);

			// Load template file
			if (!include($this->templateFile)) {
				trigger_error("Couldn't display template $this->templateFile", E_USER_ERROR);
				return false;
			};
		} else {
			// We don't have a valid template file; template gets bypassed
			print $body;
		}
		
		// Return buffer
		return ob_get_clean();
	}
	
	function Display($body) {
		print $this->GetOutput($body);
	}
	
}

?>