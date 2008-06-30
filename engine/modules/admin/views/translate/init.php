<?php

// Find localizable fields
foreach ($this->schema as $name => $info) {
	if ($info['localizable']) {
		$localizableFields[] = $name;
	}
}

// FIXME: Messy kludge below.

// Load original language data
$this->template['originalData'] = $this->FetchItem($_GET['item']);
unset($this->itemID);

// Load existing data
$originalLanguage = $_JAG['language'];
$_JAG['language'] = $_GET['to'];
$this->FetchItem($_GET['item']);
$_JAG['language'] = $originalLanguage;


?>
