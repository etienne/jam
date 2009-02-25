<?php

if (!$fields = $this->config['adminListFields']) {
	foreach ($this->schema as $name => $info) {
		if (!$_JAG['moduleFields'][$name] && !$_JAG['versionsSupportFields'][$name]) {
			$fields[] = $name;
		}
	}
}

// Add sortIndex field if we require it
if ($this->config['allowSort']) {
	array_unshift($fields, 'sortIndex');
}

// Link will be on first field; we do this before adding sortIndex because we never want the link on sortIndex (FIXME, FAUX)
$this->view['linkField'] = reset($fields);

foreach ($fields as $field) {
	if ($relatedArray = $this->GetRelatedArray($field)) {
		$this->view['relatedArrays'][$field] = $relatedArray;
	}
}

$queryParams['fields'] = $fields;
$this->view['tableFields'] = $fields;

// Determine header strings
foreach ($fields as $field) {
	// Look for string in module strings
	if (!$string = $this->strings['fields'][$field]) {
		// Look for string in global strings
		if (!$string = $_JAG['strings']['fields'][$field]) {
			// Use raw field name if all else fails
			$string = $field;
		}
	}

	$this->view['headerStrings'][$field] = $string;
}

// Determine sort order
$requestedSortField = $_GET['s'] ? $_GET['s'] : $this->config['adminListSortBy'];
if ($this->schema[$requestedSortField] && in_array($requestedSortField, $fields)) {
	$sortField = $requestedSortField;
} else {
	// If no sorting was requested, use first field
	$sortField = reset($fields);
}

// Determine type of sort field
$this->view['sortFieldType'] = $this->schema[$sortField]['type'];

// Check whether sorting order should be reversed
$reverseSort = $_GET['r'] ? 1 : 0;

// Store sort parameters in template variables
$this->view['sortField'] = $sortField;
$this->view['reverseSort'] = $reverseSort;

// Sort order is reversed for dates
if ($this->schema[$sortField]['type'] == 'datetime') {
	$reverseSort = (int)!$reverseSort;
}

// Modify query accordingly
$sortOrder = $reverseSort ? 'DESC' : 'ASC';
$queryParams['orderby'][] = $sortField .' '. $sortOrder;

// Try to sort by sort index when allowSort is set
if ($this->config['allowSort'] && $sortField != 'sortIndex') {
	$queryParams['orderby'][] = 'sortIndex ASC';
}

// Fetch data
$this->FetchItems($queryParams);

if ($this->items) {
	$editLinkPrefix = 'admin/'. $this->name .'?' .
		($_GET['s'] ? 's='. $_GET['s'] .'&amp;' : '') . 
		($_GET['r'] ? 'r='. $_GET['r'] .'&amp;' : '') . 
		'a=edit&amp;id=';
	
	// Redirect to item if requested
	if ($_GET['prev'] || $_GET['next']) {
		$requestedID = ($_GET['prev'] ? $_GET['prev'] : $_GET['next']);
		
		// Find primary key column name
		if ($this->config['keepVersions']) {
			$primaryKey = 'master';
		} else {
			$primaryKey = 'id';
		}
		
		// Find key for requested item in fetched items array
		foreach ($this->items as $key => $item) {
			if ($item[$primaryKey] == $requestedID) {
				$requestedKey = $key;
				break;
			}
		}

		// Find ID for requested item
		$previousAndNext = Arrays::GetAdjacentKeys($this->items, $requestedKey);
		if ($_GET['prev']) {
			$redirectID = $this->items[$previousAndNext[0]][$primaryKey];
		}
		if ($_GET['next']) {
			$redirectID = $this->items[$previousAndNext[1]][$primaryKey];
		}
		
		// Redirect to requested item
		if ($redirectID) {
			// Change "&amp;" to "&"; slightly kludgy
			$cleanLink = html_entity_decode($editLinkPrefix . $redirectID);
			
			// Add mode if one was supplied (kludgy)
			if ($_GET['mode']) {
				$cleanLink .= '&mode='. $_GET['mode'];
			}
			HTTP::RedirectLocal($cleanLink);
		}
	}
	
	// Store edit link prefix in template
	$this->view['editLinkPrefix'] = $editLinkPrefix;
}

?>