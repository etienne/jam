<?php

// Display friendly UI
$this->template['message'] = $_JAG['strings']['admin']['deleteConfirmation'];

// Build confirmation form
$confirmForm = new Form();
$confirmForm->AddHidden('module', $this->name);
$confirmForm->AddHidden('master', $this->itemID);
$confirmForm->AddHidden('action', 'delete');
$confirmForm->AddSubmit('cancel', $_JAG['strings']['admin']['cancel']);
$confirmForm->AddSubmit('delete', $_JAG['strings']['admin']['delete']);
$this->template['confirmForm'] = $confirmForm->GetString();


?>
