<?php

$queryArray = array(
	'fields' => array('id', 'modified'),
	'from' => $this->name,
	'where' => array(
		'current != true',
		array(
			'id = '. $this->itemID,
			'master = '. $this->itemID
		)
	),
	'orderby' => 'modified DESC'
);

$query = new Query($queryArray);
$versions = $query->GetSimpleArray();

foreach ($versions as $id => $timestamp) {
	$date = new Date($timestamp);
	$dateString = $date->SmartDate() .' '. $date->Time();
	$paths[$id] = a('admin/'. $this->name .'?a=revert&revertid='. $id, $dateString);
}

$this->template['paths'] = $paths;

?>
