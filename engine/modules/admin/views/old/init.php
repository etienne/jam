<?php

$queryArray = array(
	'fields' => array('id', 'timestamp'),
	'from' => $this->name,
	'where' => array(
		'current != true',
		array(
			'id = '. $this->itemID,
			'master = '. $this->itemID
		)
	),
	'orderby' => 'timestamp DESC'
);

$query = new Query($queryArray);
$versions = $query->GetSimpleArray();

foreach ($versions as $id => $timestamp) {
	$date = new Date($timestamp);
	$dateString = $date->SmartDate() .' '. $date->Time();
	$paths[$id] = a('admin/'. $this->name .'?a=revert&id='. $id, $dateString);
}

$this->template['paths'] = $paths;

?>
