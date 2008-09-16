<?php

// Determine whether we can insert items
if ($this->config['canInsert']) {
	$canInsert = $_JAG['user']->HasPrivilege($this->config['canInsert']);
} else {
	$canInsert = true;
}

$this->template['canInsert'] = $canInsert;

?>