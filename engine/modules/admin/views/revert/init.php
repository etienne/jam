<?php

// Fetch data for this specific version
$revertID = $_GET['revertid'];
$data = Query::SingleRow($this->name, 'id = '. $revertID);

// Load data into module
$this->LoadData($data);

// Display friendly UI
$masterID = $this->item['master'] ? $this->item['master'] : $this->item['id'];
$this->view['backLink'] = a(
	'admin/'. $this->name .'?a=old&id='. $masterID,
	$_JAG['strings']['admin']['backRevert']
);
$this->view['masterID'] = $masterID;
$this->view['revertID'] = $revertID;
$this->view['message'] = $_JAG['strings']['admin']['revertConfirmation'];

// Build confirmation form
$this->view['confirmForm'] = new Form();

?>
