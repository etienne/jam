<?php

// Get list of contexts for module associated with this file
$moduleName = $_JAG['installedModules'][$this->items[$this->itemID]['module']];
$contexts = Module::ParseConfigFile($moduleName, 'config/images.ini', true);

// Get path to file
$file = $_JAG['filesDirectory'] . $this->itemID;

if ($context = $contexts[$_GET['context']]) {
	$image = new Image($file);
	$image->OutputResizedImage($context['width'], $context['height'], $context['quality']);
} else {
	// Set MIME type, if available
	if ($this->items[$this->itemID]['type']) {
		header('Content-type: '. $this->items[$this->itemID]['type']);
	}
	
	// Determine file size
	if ($fileSize = filesize($file)) {
		header('Content-length: '. $fileSize);
	}
	
	// Read file directly from 'files' directory
	readfile($file);
}

// Don't display anything else; this also bypasses caching, which is good
exit;

?>
