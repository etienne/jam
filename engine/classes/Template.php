<?php

class Template {
	
	var $variables;
	var $templateFile;
	
	function Template($name) {
		// Make sure requested template exists
		$requestedTemplate = 'templates/'. $name .'.php';
		if (Filesystem::FileExistsInIncludePath($requestedTemplate)) {
			$this->templateFile = $requestedTemplate;
		} else {
			return false;
		}
	}
	
	function NewVariable($name, $value) {
		$this->variables[$name] = $value;
	}
	
	function AppendVariable($name, $value) {
		if (isset($this->variables[$name]) && !is_array($this->variables[$name])) {
			// Variable is set but is not already an array; convert into an array
			$previousValue = $this->variables[$name];
			$this->variables[$name] = array($previousValue);
		}
		$this->variables[$name][] = $value;
	}
	
	function Display($body) {
		global $_JAG;
		
		// Make sure we have a valid template file
		if ($this->templateFile) {
			// If 'body' variable is not set, use $body
			if (!$this->variable['body']) {
				$this->variables['body'] = $body;
			}

			// Load $this->variables into local symbol table
			extract($this->variables);

			// Load template file
			if (include($this->templateFile)) {
				return true;
			} else {
				trigger_error("Couldn't display template $this->templateFile", E_USER_ERROR);
				return false;
			};
		} else {
			// We don't have a valid template file; template gets bypassed
			print $body;
		}
		
	}
	
}

?>