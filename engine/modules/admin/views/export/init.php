<?php

$_JAG['title'] = $this->strings['adminTitle'];

// Check whether we should display anything at all
if ($exportFields = $this->config['adminExportFields']) {
	// Fetch data according to export fields
	$queryParams = array('fields' => $exportFields);
	
	// Always fetch primary key field
	if ($this->config['keepVersions']) {
		$primaryKeyField = 'master';
	} else {
		$primaryKeyField = 'id';
	}
	$queryParams['fields'][] = $primaryKeyField;
	
	// Use first field as sort field
	$sortField = current($exportFields);
	$queryParams['orderby'] = $sortField .' ASC';
	
	// Fetch data
	$this->FetchItems($queryParams);
	
	foreach ($exportFields as $field) {
		// Get related arrays
		if ($relatedArray = $this->GetRelatedArray($field)) {
			$relatedArrays[$field] = $relatedArray;
		}
		
		// Determiner headers
		$this->view['headers'][] = $this->strings['fields'][$field];
	}
	
	// Assemble into final array
	foreach ($this->items as $id => $item) {
		foreach ($exportFields as $field) {
			if ($relatedArrays[$field]) {
				// Field has related array
				$value = $relatedArrays[$field][$item[$field]];
			} else {
				// Check whether field type is boolean
				if ($this->schema[$field]['type'] == 'bool') {
					// Replace boolean value with human-readable string
					$value = $item[$field] ? $_JAG['strings']['words']['affirmative'] : $_JAG['strings']['words']['negative'];
				} else {
					// Use straight value
					$value = $item[$field];
				}
			}
			
			// Fix weird bug with non-breaking spaces
			$value = str_replace(' ', ' ', $value);
						
			$data[$item[$primaryKeyField]][] = $value;
		}
	}
	
	// Add related module data if applicable
	if ($relatedModules = $this->config['adminExportRelatedModules']) {
		foreach ($relatedModules as $relatedModuleName) {
			// Create new module object
			$relatedModule = Module::GetNewModule($relatedModuleName);
			
			// Find field that relates to this module
			foreach ($relatedModule->schema as $field => $info) {
				if ($info['relatedModule'] == $this->name) {
					$relatedModuleField = $field;
					break;
				}
			}
			
			// We absolutely need a field to continue
			if (!$relatedModuleField) {
				break;
			}
			
			// Add relevant header
			$this->view['headers'][] = $relatedModule->strings['adminTitle'];

			// Fetch data
			$keyQueryParams = Module::ParseConfigFile($relatedModuleName, 'config/keyQuery.ini', true);
			$params = $keyQueryParams;
			$params['fields'][] = $relatedModuleField;
			$relatedModuleData = $relatedModule->FetchItems($params);
			
			// Obtain name of key field in related module (sneaky)
			$keyFields = $keyQueryParams['fields'];
			end($keyFields);
			$relatedKeyField = key($keyFields);
			
			// Populate data array with data from related module
			foreach ($relatedModuleData as $relatedItem) {
				$relatedID = $relatedItem[$relatedModuleField];
				if ($data[$relatedID]) {
					$data[$relatedID][$relatedModule->name][] = $relatedItem[$relatedKeyField];
				}
			}
			
			// Convert arrays to HTML lists
			foreach ($data as $id => $item) {
				if ($array = $item[$relatedModule->name]) {
					$listString = '';
					foreach ($array as $listItem) {
						$listString .= e('li', $listItem);
					}
					$list = e('ul', $listString);
					$data[$id][$relatedModule->name] = $list;
				}
			}
		}
	}
	
	// Store in template
	$this->view['data']	= $data;
}

?>