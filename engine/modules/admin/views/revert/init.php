<?php

// Fetch data for this specific version
$revertID = $_GET['revertid'];
$data = Query::SingleRow($this->name, 'id = '. $revertID);

// Load data into module
$this->LoadData($data);

// Display friendly UI
$masterID = $this->item['master'] ? $this->item['master'] : $this->item['id'];
$this->template['backLink'] = a(
	'admin/'. $this->name .'?a=old&id='. $masterID,
	$_JAG['strings']['admin']['backRevert']
);
$this->template['masterID'] = $masterID;
$this->template['revertID'] = $revertID;
$this->template['message'] = $_JAG['strings']['admin']['revertConfirmation'];

// Build confirmation form
$this->template['confirmForm'] = new Form();

?>
