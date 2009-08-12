<?php

class AdminModule extends Module {
	
	var $nestedModule;
	
	function Initialize() {
		global $_JAM;
		
		// Install modules that require installation
		$modulesInstalled = false;
		foreach ($_JAM->availableModules as $moduleName) {
			if (!in_array($moduleName, $_JAM->installedModules)) {
				// Module needs to be installed
				$module = Module::GetNewModule($moduleName);
				if (!$module->Install()) {
					trigger_error("Couldn't install module " . $moduleName, E_USER_ERROR);
					return false;
				}
				$modulesInstalled = true;
			}
		}
		
		// Reload the requested page if we installed any modules
		if ($modulesInstalled) {
			HTTP::ReloadCurrentURL();
		}
		
		return true;
	}
	
	function NestModule($name, $item = '') {
		$this->nestedModule = Module::GetNewModule($name, $item, true);
		$this->nestedModule->AttachParent($this);
		$this->nestedModule->FinishSetup();
		return $this->nestedModule;
	}
	
	function Display() {
		global $_JAM;
		
		// Load correct view
		if ($this->itemID) {
			// $this->itemID contains the ID of the requested module in the _modules table
			$moduleName = $_JAM->installedModules[$this->itemID];
			
			// Add requested module
			// Note: $_GET['id'] is not necessarily set
			$this->NestModule($moduleName, $_GET['id']);

			if (!$this->nestedModule->LoadView($_GET['a'])) {
				$this->nestedModule->LoadView('default');
			}
		}
	}
	
	function AdminDefaultViewController() {
		global $_JAM;
		
		// Determine whether we can insert items
		if ($this->nestedModule->config['canInsert']) {
			$canInsert = $_JAM->user->HasPrivilege($this->nestedModule->config['canInsert']);
		} else {
			$canInsert = true;
		}

		$this->nestedModule->view['canInsert'] = $canInsert;

		// Determine string for "Add item"
		$this->nestedModule->view['indeterminateItem'] = $_JAM->strings['admin']['add'] .' ';
		if ($this->nestedModule->strings['indeterminateItem']) {
			$this->nestedModule->view['indeterminateItem'] .= $this->nestedModule->strings['indeterminateItem'];
		} else {
			$this->nestedModule->view['indeterminateItem'] .= $_JAM->strings['admin']['genericIndeterminate'];
		}
	}

	function AdminDeleteViewController() {
		global $_JAM;

		// Display friendly UI
		$this->nestedModule->view['message'] = $_JAM->strings['admin']['deleteConfirmation'];

		// Build confirmation form
		$this->nestedModule->view['confirmForm'] = new Form();
	}

	function AdminEditViewController() {
		global $_JAM;

		// Determine link back to list view
		$this->nestedModule->view['linkToListView'] = $_JAM->request;
		if ($_GET['s']) {
			$this->nestedModule->view['linkToListView'] .= '?'.
				($_GET['s'] ? 's='. $_GET['s'] : '') . 
				($_GET['r'] ? '&amp;r='. $_GET['r'] : '');
		}

		// Get links to previous and next items
		if ($_GET['id']) {
			$basePath = $this->nestedModule->view['linkToListView'];
			// Add '?' if it's not present; slightly kludgy
			$basePath .= (strpos($basePath, '?') === false ? '?' : '&amp;');
			$this->nestedModule->view['linkToPrevious'] = $basePath .'prev='. $_GET['id'];
			$this->nestedModule->view['linkToNext'] = $basePath .'next='. $_GET['id'];
		}
	}

	function AdminExportViewController() {
		global $_JAM;

		$_JAM->title = $this->nestedModule->strings['adminTitle'];

		// Check whether we should display anything at all
		if ($exportFields = $this->nestedModule->config['adminExportFields']) {
			// Fetch data according to export fields
			$queryParams = array('fields' => $exportFields);

			// Always fetch primary key field
			if ($this->nestedModule->config['keepVersions']) {
				$primaryKeyField = 'master';
			} else {
				$primaryKeyField = 'id';
			}
			$queryParams['fields'][] = $primaryKeyField;

			// Use first field as sort field
			$sortField = current($exportFields);
			$queryParams['orderby'] = $sortField .' ASC';

			// Fetch data
			$this->nestedModule->FetchItems($queryParams);

			foreach ($exportFields as $field) {
				// Get related arrays
				if ($relatedArray = $this->nestedModule->GetRelatedArray($field)) {
					$relatedArrays[$field] = $relatedArray;
				}

				// Determiner headers
				$this->nestedModule->view['headers'][] = $this->nestedModule->strings['fields'][$field];
			}

			// Assemble into final array
			foreach ($this->nestedModule->items as $id => $item) {
				foreach ($exportFields as $field) {
					if ($relatedArrays[$field]) {
						// Field has related array
						$value = $relatedArrays[$field][$item[$field]];
					} else {
						// Check whether field type is boolean
						if ($this->nestedModule->schema[$field]['type'] == 'bool') {
							// Replace boolean value with human-readable string
							$value = $item[$field] ? $_JAM->strings['words']['affirmative'] : $_JAM->strings['words']['negative'];
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
			if ($relatedModules = $this->nestedModule->config['adminExportRelatedModules']) {
				foreach ($relatedModules as $relatedModuleName) {
					// Create new module object
					$relatedModule = Module::GetNewModule($relatedModuleName);

					// Find field that relates to this module
					foreach ($relatedModule->schema as $field => $info) {
						if ($info['relatedModule'] == $this->nestedModule->name) {
							$relatedModuleField = $field;
							break;
						}
					}

					// We absolutely need a field to continue
					if (!$relatedModuleField) {
						break;
					}

					// Add relevant header
					$this->nestedModule->view['headers'][] = $relatedModule->strings['adminTitle'];

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
			$this->nestedModule->view['data']	= $data;
		}
	}

	function AdminFormViewController() {
		$this->nestedModule->AutoForm();
	}

	function AdminListViewController() {
		global $_JAM;
		
		if (!$fields = $this->nestedModule->config['adminListFields']) {
			foreach ($this->nestedModule->schema as $name => $info) {
				if (!$_JAM->moduleFields[$name] && !$_JAM->versionsSupportFields[$name]) {
					$fields[] = $name;
				}
			}
		}

		// Add sortIndex field if we require it
		if ($this->nestedModule->config['allowSort']) {
			array_unshift($fields, 'sortIndex');
		}

		// Link will be on first field; we do this before adding sortIndex because we never want the link on sortIndex (FIXME, FAUX)
		$this->nestedModule->view['linkField'] = reset($fields);

		foreach ($fields as $field) {
			if ($relatedArray = $this->nestedModule->GetRelatedArray($field)) {
				$this->nestedModule->view['relatedArrays'][$field] = $relatedArray;
			}
		}

		$queryParams['fields'] = $fields;
		$this->nestedModule->view['tableFields'] = $fields;

		// Determine header strings
		foreach ($fields as $field) {
			// Look for string in module strings
			if (!$string = $this->nestedModule->strings['fields'][$field]) {
				// Look for string in global strings
				if (!$string = $_JAM->strings['fields'][$field]) {
					// Use raw field name if all else fails
					$string = $field;
				}
			}

			$this->nestedModule->view['headerStrings'][$field] = $string;
		}

		// Determine sort order
		$requestedSortField = $_GET['s'] ? $_GET['s'] : $this->nestedModule->config['adminListSortBy'];
		if ($this->nestedModule->schema[$requestedSortField] && in_array($requestedSortField, $fields)) {
			$sortField = $requestedSortField;
		} else {
			// If no sorting was requested, use first field
			$sortField = reset($fields);
		}

		// Determine type of sort field
		$this->nestedModule->view['sortFieldType'] = $this->nestedModule->schema[$sortField]['type'];

		// Check whether sorting order should be reversed
		$reverseSort = $_GET['r'] ? 1 : 0;

		// Store sort parameters in template variables
		$this->nestedModule->view['sortField'] = $sortField;
		$this->nestedModule->view['reverseSort'] = $reverseSort;

		// Sort order is reversed for dates
		if ($this->nestedModule->schema[$sortField]['type'] == 'datetime') {
			$reverseSort = (int)!$reverseSort;
		}

		// Modify query accordingly
		$sortOrder = $reverseSort ? 'DESC' : 'ASC';
		$queryParams['orderby'][] = $sortField .' '. $sortOrder;

		// Try to sort by sort index when allowSort is set
		if ($this->nestedModule->config['allowSort'] && $sortField != 'sortIndex') {
			$queryParams['orderby'][] = 'sortIndex ASC';
		}

		// Fetch data
		$this->nestedModule->FetchItems($queryParams);
		
		if ($this->nestedModule->items) {
			$editLinkPrefix = 'admin/'. $this->nestedModule->name .'?' .
				($_GET['s'] ? 's='. $_GET['s'] .'&amp;' : '') . 
				($_GET['r'] ? 'r='. $_GET['r'] .'&amp;' : '') . 
				'a=edit&amp;id=';

			// Redirect to item if requested
			if ($_GET['prev'] || $_GET['next']) {
				$requestedID = ($_GET['prev'] ? $_GET['prev'] : $_GET['next']);

				// Find primary key column name
				if ($this->nestedModule->config['keepVersions']) {
					$primaryKey = 'master';
				} else {
					$primaryKey = 'id';
				}

				// Find key for requested item in fetched items array
				foreach ($this->nestedModule->items as $key => $item) {
					if ($item[$primaryKey] == $requestedID) {
						$requestedKey = $key;
						break;
					}
				}

				// Find ID for requested item
				$previousAndNext = Arrays::GetAdjacentKeys($this->nestedModule->items, $requestedKey);
				if ($_GET['prev']) {
					$redirectID = $this->nestedModule->items[$previousAndNext[0]][$primaryKey];
				}
				if ($_GET['next']) {
					$redirectID = $this->nestedModule->items[$previousAndNext[1]][$primaryKey];
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
			$this->nestedModule->view['editLinkPrefix'] = $editLinkPrefix;
		}
	}

	function AdminOldViewController() {
		$queryArray = array(
			'fields' => array('id', 'modified'),
			'from' => $this->nestedModule->name,
			'where' => array(
				'current != true',
				array(
					'id = '. $this->nestedModule->itemID,
					'master = '. $this->nestedModule->itemID
				)
			),
			'orderby' => 'modified DESC'
		);

		$query = new Query($queryArray);
		$versions = $query->GetSimpleArray();

		foreach ($versions as $id => $timestamp) {
			$date = new Date($timestamp);
			$dateString = $date->SmartDate() .' '. $date->Time();
			$paths[$id] = a('admin/'. $this->nestedModule->name .'?a=revert&revertid='. $id, $dateString);
		}

		$this->nestedModule->view['paths'] = $paths;
	}

	function AdminRevertViewController() {
		global $_JAM;

		// Fetch data for this specific version
		$revertID = $_GET['revertid'];
		$data = Query::SingleRow($this->nestedModule->name, 'id = '. $revertID);

		// Load data into module
		$this->nestedModule->LoadData($data);

		// Display friendly UI
		$masterID = $this->nestedModule->item['master'] ? $this->nestedModule->item['master'] : $this->nestedModule->item['id'];
		$this->nestedModule->view['backLink'] = a(
			'admin/'. $this->nestedModule->name .'?a=old&id='. $masterID,
			$_JAM->strings['admin']['backRevert']
		);
		$this->nestedModule->view['masterID'] = $masterID;
		$this->nestedModule->view['revertID'] = $revertID;
		$this->nestedModule->view['message'] = $_JAM->strings['admin']['revertConfirmation'];

		// Build confirmation form
		$this->nestedModule->view['confirmForm'] = new Form();
	}

	function AdminTranslateViewController() {
		global $_JAM;

		// Find localizable fields
		foreach ($this->nestedModule->schema as $name => $info) {
			if ($info['localizable']) {
				$localizableFields[] = $name;
			}
		}

		// FIXME: Messy kludge below.

		// Load original language data
		$this->nestedModule->view['originalData'] = $this->nestedModule->FetchItem($_GET['item']);
		unset($this->nestedModule->itemID);

		// Load existing data
		$originalLanguage = $_JAM->language;
		$_JAM->language = $_GET['to'];
		$this->nestedModule->FetchItem($_GET['item']);
		$_JAM->language = $originalLanguage;
	}

	function DeleteAction($module) {
		if ($_POST['delete']) {
			// Delete
			$deleteResult = $module->DeleteItem($_POST['master']);
			if ($deleteResult === true) {
				HTTP::ReloadCurrentURL('?m=deleted');
				break;
			} elseif ($deleteResult == ERROR_FOREIGN_KEY_CONSTRAINT) {
				HTTP::ReloadCurrentURL('?m=errorForeignKey');
				break;
			}
		} else {
			// Cancel; go back to previous versions list
			HTTP::ReloadCurrentURL('?a=edit&id='. $_POST['master']);
		}
	}
	
	function RevertAction($module) {
		if ($_POST['revert']) {
			// Revert to specific version
			if ($module->Revert($_POST['revertID'])) {
				HTTP::ReloadCurrentURL('?a=edit&id='. $_POST['master']);
			}
		} else {
			// Cancel; go back to item form
			HTTP::ReloadCurrentURL('?a=edit&id='. $_POST['master']);
		}
	}
	
}

?>
