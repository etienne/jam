<?php

// Get list of contexts
$contexts = IniFile::Parse('engine/config/imageContexts.ini', true);
if ($appContexts = IniFile::Parse('app/config/imageContexts.ini', true)) {
	$contexts += $appContexts;
}

// Get path to file
$file = $_JAG['filesDirectory'] . $this->itemID;

if ($context = $contexts[$_GET['context']]) {
	$image = new Image($file);
	
	// Set dimensions according to context
	$width = $context['width'];
	$height = $context['height'];
	
	if ($context['allowScaleUp'] === '') {
		// Scale up is forbidden; check whether we're scaling up
		if ($context['width'] > $image->width || $context['height'] > $image->height) {
			// We're indeed scaling up; override context dimensions with actual file dimensions
			$width = $image->width;
			$height = $image->height;
		}
	}

	$image->OutputResizedImage($width, $height);
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
