<?php

// Fetch data for this specific version
$data = Query::SingleRow($this->name, 'id = '. $_GET['id']);

// Load data into module
$this->LoadData($data);

// Display friendly UI
$masterID = $this->items[$this->itemID]['master'] ? $this->items[$this->itemID]['master'] : $this->items[$this->itemID]['id'];
$this->template['backLink'] = a('admin/'. $this->name .'?a=old&id='. $masterID, $_JAG['strings']['admin']['backRevert']);
$this->template['message'] = $_JAG['strings']['admin']['revertConfirmation'];

// Build confirmation form
$confirmForm = new Form();
$confirmForm->AddHidden('module', $this->name);
$confirmForm->AddHidden('revertID', $_GET['id']);
$confirmForm->AddHidden('master', $masterID);
$confirmForm->AddHidden('action', 'revert');
$confirmForm->AddSubmit('cancel', $_JAG['strings']['admin']['cancel']);
$confirmForm->AddSubmit('revert', $_JAG['strings']['admin']['revert']);
$this->template['confirmForm'] = $confirmForm->GetString();


?>
