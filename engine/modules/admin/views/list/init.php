<?php

if (!$fields = $this->config['adminListFields']) {
	foreach ($this->schema as $name => $info) {
		if (!$_JAG['moduleFields'][$name] && !$_JAG['versionsSupportFields'][$name]) {
			$fields[] = $name;
		}
	}
}

// Add sortIndex field if available
if ($this->config['allowSort']) {
	array_unshift($fields, 'sortIndex');
}

foreach ($fields as $field) {
	if ($relatedArray = $this->GetRelatedArray($field)) {
		$this->template['relatedArrays'][$field] = $relatedArray;
	}
}

$queryParams['fields'] = $fields;
$this->template['tableFields'] = $fields;

// Determine sort order
$requestedSortField = $_GET['s'] ? $_GET['s'] : $this->config['adminListSortBy'];
if ($this->schema[$requestedSortField] && in_array($requestedSortField, $fields)) {
	$sortField = $requestedSortField;
} else {
	// If no sorting was requested, use first field
	$sortField = current($fields);
}

// Determine type of sort field
$this->template['sortFieldType'] = $this->schema[$sortField]['type'];

// Check whether sorting order should be reversed
$reverseSort = $_GET['r'] ? 1 : 0;

// Store sort parameters in template variables
$this->template['sortField'] = $sortField;
$this->template['reverseSort'] = $reverseSort;

// Sort order is reversed for dates
if ($this->schema[$sortField]['type'] == 'datetime') {
	$reverseSort = (int)!$reverseSort;
}

// Modify query accordingly
$sortOrder = $reverseSort ? 'DESC' : 'ASC';
$queryParams['orderby'] = $sortField .' '. $sortOrder;

// Fetch data
$this->FetchItems($queryParams);

if ($this->items) {
	$this->template['editLinkPrefix'] = 'admin/'. $this->name .'?a=edit&id=';
}

?>