<?php

// Determine link back to list view
$this->view['linkToListView'] = $_JAG['request'];
if ($_GET['s']) {
	$this->view['linkToListView'] .= '?'.
		($_GET['s'] ? 's='. $_GET['s'] : '') . 
		($_GET['r'] ? '&amp;r='. $_GET['r'] : '');
}

// Get links to previous and next items
if ($_GET['id']) {
	$basePath = $this->view['linkToListView'];
	// Add '?' if it's not present; slightly kludgy
	$basePath .= (strpos($basePath, '?') === false ? '?' : '&amp;');
	$this->view['linkToPrevious'] = $basePath .'prev='. $_GET['id'];
	$this->view['linkToNext'] = $basePath .'next='. $_GET['id'];
}

?>