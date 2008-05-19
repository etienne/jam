<?php

require_once('classes/Database.php');
require_once('classes/Date.php');
require_once('classes/Filesystem.php');
require_once('classes/Form.php');
require_once('classes/HTTP.php');
require_once('classes/Path.php');
require_once('classes/Query.php');
require_once('classes/Query.php');

class Module {

	var $name;
	var $moduleID;
	var $itemID;
	var $config;
	var $schema;

	var $modulePath;
	var $keyFieldName;
	var $hasFiles;
	var $hasMulti;
	var $multiRelatedModules;

	var $adminMenuStrings;
	var $strings;
	
	var $parentModule;
	
	var $items;
	var $item;
	var $rawData;
	
	var $postData = array();
	var $missingData;
	var $invalidData;
	var $fileUploadError;
	var $template = array();
	
	/*
	 * Constructor
	 */
	
	function Module($name, $item = '', $parentModule = false) {
		global $_JAG;
		$this->name = $name;
		$this->itemID = $item;
		$this->parentModule = $parentModule;
		
		// Check whether this is an app-level or engine-level module
		$modulePathRoot = in_array($this->name, $_JAG['appModules']) ? 'app' : 'engine';
		$this->modulePath = $modulePathRoot .'/modules/'. $this->name .'/';
		
		// Make sure this module exists
		if (!$_JAG['availableModules'][$this->name]) {
			return false;
		}
		
		// Load configuration files
		$this->config = IniFile::Parse($this->modulePath .'config/config.ini', true);
		$this->strings = IniFile::Parse($this->modulePath .'strings/'. $_JAG['language'] .'.ini', true);
		$this->adminMenuStrings = IniFile::Parse($this->modulePath .'config/admin.ini');
		
		// Get info for this module's table, if there is one
		if ($schema = IniFile::Parse($this->modulePath .'config/table.ini', true)) {

			// Merge with standard basic fields if applicable
			if ($this->config['useCustomTable']) {
				$this->schema = $schema;
			} else {
				$this->schema = $_JAG['moduleFields'];
				if ($this->config['keepVersions']) {
					// Additional fields are needed for versions support
					$this->schema += $_JAG['versionsSupportFields'];
				}
				$this->schema += $schema;
			}

			foreach ($this->schema as $name => $info) {
				// Find name of key field
				if ($info['key'] == true) {
					$this->keyFieldName = $name;
				}
				
				// Look for specific field types
				switch ($info['type']) {
					case 'file':
						$this->hasFiles = true;
						break;
					case 'multi':
						$this->hasMulti = true;
						$relatedModuleName = $info['relatedModule'];
						$relatedModuleID = array_search($relatedModuleName, $_JAG['installedModules']);
						$this->multiRelatedModules[$relatedModuleID] = $name;
						break;
				}
			}
		}
		
		// Make sure module is installed and get ID for this module
		if ($this->moduleID = array_search($this->name, $_JAG['installedModules'])) {
			
			// Update data if this module has a table and we have the right POST data
			if ($this->schema && $_POST['module'] == $this->name) {
				$this->ProcessData();
			}
			
			// Fetch data for this item, if one was specified
			if ($this->itemID && $this->schema) {
				$this->FetchItem($this->itemID);
			}

			// Run initialization method if one was defined
			if (method_exists($this, 'Initialize')) {
				$this->Initialize();
			}
		}
		
	}
	
	/*
	 * Static
	 */
	
	function GetNewModule($name, $item = '', $parentModule = false) {
		global $_JAG;
		if (!$_JAG['availableModules'][$name]) trigger_error("Couldn't create new module because '". $name ."' module does not exist", E_USER_ERROR);
		$className = $name .'Module';
		$classPath = 'modules/'. $name .'/'. $className .'.php';
		if (Filesystem::FileExistsInIncludePath($classPath)) {
			// There is a custom module class; load it and create new instance
			require_once($classPath);
			return new $className($name, $item, $parentModule);
		} else {
			// There is no custom module class; use plain Module class
			return new Module($name, $item, $parentModule);
		}
	}
	
	function ParseConfigFile($moduleName, $iniFile, $processSections = false) {
		global $_JAG;
		
		// Determine whether requested module is a custom (app-specific) or engine module
		$iniFileRoot = in_array($moduleName, $_JAG['appModules']) ? 'app' : 'engine';
		
		// Build path to config file
		$iniFilePath = $iniFileRoot .'/modules/'. $moduleName .'/'. $iniFile;
		
		return IniFile::Parse($iniFilePath, $processSections);
	}
	
	/*
	 * Private
	 */

	function Install() {
		global $_JAG;
		
		// Make sure table has not already been installed
		if (Query::SingleValue('_modules', 'id', "name = '". $this->name ."'")) {
			trigger_error('Module is already installed', E_USER_WARNING);
			return false;
		}
		
		// Determine whether we need a table at all
		if ($this->schema) {
			// Create table
			if (!Database::CreateTable($this->name, $this->schema)) {
				trigger_error("Couldn't create table for module ". $this->name, E_USER_ERROR);
				return false;
			}
		}
		
		// Add entry to _modules table
		$params = array('name' => $this->name);
		if (Database::Insert('_modules', $params)) {
			// Get ID of the row we just inserted
			$this->moduleID = Database::GetLastInsertID();
			
			// Add admin path to _paths table if necessary
			if ($this->adminMenuStrings) {
				$adminModuleID = array_search('admin', $_JAG['installedModules']);
				if (!Path::Insert('admin/'. $this->name, $adminModuleID, $this->moduleID)) {
					trigger_error("Couldn't add admin path for module ". $this->name, E_USER_ERROR);
					return false;
				}
			}
			
			// Add paths to _paths table if needed
			if ($this->config['path']) {
				// Add paths for each language
				foreach ($this->config['path'] as $language => $path) {
					if (!Path::Insert($path, $this->moduleID, 0, true, $language)) {
						trigger_error("Could't add path for module ". $this->name, E_USER_ERROR);
						return false;
					}
				}
			}
			
			return true;
		} else {
			trigger_error("Couldn't install module ". $this->name, E_USER_ERROR);
			return false;
		}
		
	}

	function NestModule($name, $item = '') {
		return Module::GetNewModule($name, $item, &$this);
	}
	
	function DisplayNestedModule($name, $item = '') {
		$module = $this->NestModule($name, $item);
		$module->Display();
	}
	
	function FetchItem($id) {
		global $_JAG;
		
		$params = array(
			'where' => ($this->config['keepVersions'] ? 'master' : $this->name .'.id') .' = '. $id,
			'limit' => 1
		);
		
		foreach($this->schema as $name => $info) {
			$params['fields'][$name] = $this->name .'.'. $name;
		}
	
		$this->itemID = $id;
		$item = $this->FetchItems($params);
		return $this->item = $item[$id];
	}
	
	function FetchItems($queryParams = '') {
		$query = new Query();
		$query->AddFrom($this->name);
		
		if ($this->config['keepVersions']) {
			// This is a multiversions table; fetch 'master' field
			$query->AddFields(array(
				'master' => 'IF('. $this->name .'.master IS NULL, '. $this->name .'.id, '. $this->name .'.master)'
			));
			$query->AddWhere($this->name .'.current = TRUE');
		} else {
			// This is a standard table; fetch 'id' field
			$query->AddFields(array('id' => $this->name .'.id'));
		}
		
		// Add custom parameters
		$query->LoadParameters($queryParams);
		
		// Order by master if we're keeping versions
		if ($this->config['keepVersions']) {
			$query->AddOrderBy('master DESC');
		}
		
		// Load paths
		$query->AddFields(array('path' => '_paths.path'));
		$joinTable = '_paths';
		$joinConditions = array(
			'_paths.module = '. $this->moduleID,
			'_paths.current = 1',
			'_paths.item = '. $this->name .'.id'
		);
		$query->AddJoin($this->name, $joinTable, $joinConditions);
		
		// Fetch data for related modules
		foreach($this->schema as $name => $info) {
			if (@in_array($name, $queryParams['fields']) || @in_array($this->name .'.'. $name, $queryParams['fields'])) {
				if (($relatedModule = $info['relatedModule']) && $relatedModule != 'users') {
					$relatedModuleSchema = Module::ParseConfigFile($relatedModule, 'config/table.ini', true);
					foreach($relatedModuleSchema as $foreignName => $foreignInfo) {
						$fields[$name .'_'. $foreignName] = $relatedModule .'.'. $foreignName;
					}
					$query->AddFields($fields);
					$joinTable = $relatedModule;
					$joinCondition = $this->name .'.'. $name .' = '. $relatedModule .'.id';
					$query->AddJoin($this->name, $joinTable, $joinCondition);
				}
			}
		}
				
		// Fetch actual module data
		if ($dataArray = $query->GetArray()) {
			
			// Keep raw data from database
			$this->rawData = $dataArray;
			
			// Load data for 'multi' fields
			if ($this->hasMulti) {
				$where = 'frommodule = '. $this->moduleID;
				$multiArray = Query::FullResults('_relationships', $where);
				foreach($dataArray as $id => $item) {
					foreach($multiArray as $multiData) {
						if($multiData['fromid'] == $id) {
							$dataArray[$id][$this->multiRelatedModules[$multiData['tomodule']]][] = $multiData['toid'];
						}
					}
				}
			}
			
			// Post-process data
			foreach($this->schema as $name => $info) {
				foreach ($dataArray as $id => $data) {
					if ($dataArray[$id][$name]) {
						switch ($info['type']) {
							case 'string':
							case 'text':
							case 'shorttext':
								if (strstr($data[$name], "\n") !== false) {
									// String contains newline characters; format as multiline text
									$dataArray[$id][$name] = TextRenderer::TextToHTML($data[$name]);
								} else {
									// String is a single line; format as single line
									$dataArray[$id][$name] = TextRenderer::SmartizeText($data[$name]);
								}
								break;
							case 'datetime':
							case 'timestamp':
								$dataArray[$id][$name] = new Date($data[$name]);
								break;
							case 'file':
								$dataArray[$id][$name] = $this->NestModule('files', $data[$name]);
								break;
						}
					}
				}
			}
			
			if ($this->items) {
				// If $this->items is already set, don't overwrite it
				return $dataArray;
			} else {
				return $this->items = $dataArray;
			}
		} else {
			return false;
		}

	}
	
	function LoadData($data) {
		if ($this->itemID) {
			return $this->item = $data;
		} else {
			return false;
		}
	}
	
	function Display() {
		// Determine whether we're looking at a single item in the module
		$view = $this->itemID ? 'item' : 'default';
		return $this->LoadView($view) ? true : false;
	}
	
	function LoadView($view) {
		global $_JAG;
		
		if (!$view) return false;
		
		// Get parent module's name
		$parentName = $this->parentModule->name;
		
		// Determine view
		$viewPath = $this->modulePath .'views/'. ($parentName ? $parentName . '.' : '') . $view;
		if ($_JAG['rootModuleName'] == 'admin') {
			// If we're in admin mode, only try to load admin views, and only for the required module
			$adminViewPath = 'engine/modules/admin/views/'. $view;
			if (file_exists($viewPath) && $parentName == 'admin') {
				$viewDir = $viewPath;
			} elseif (file_exists($adminViewPath)) {
				$viewDir = $adminViewPath;
			}
		} else {
			// If we're not in admin mode, try to load a regular view
			if (file_exists($viewPath)) {
				$viewDir = $viewPath;
			}
		}
		
		if (!$viewDir) {
			return false;
		}
		
		// Fetch module data if applicable
		if ($this->schema && !$this->items) {
			if ($this->itemID) {
				$this->FetchItem($this->itemID);
			} elseif ($queryParams = IniFile::Parse($viewDir . '/query.ini', true)) {
				// Make references to fields from this module explicit
				foreach($queryParams['fields'] as $alias => $field) {
					if (!is_string($alias) && $this->schema[$field]) {
						unset($queryParams['fields'][$alias]);
						$queryParams['fields'][$field] = $this->name .'.'. $field;
					}
				}
				$this->FetchItems($queryParams);
			}
		}
		
		// Run initialization script, if present
		$initScript = $viewDir .'/init.php';
		if (file_exists($initScript)) {
			include $initScript;
		}
		
		// Load module data into template variables
		if ($this->item) extract($this->item);
		if ($this->items) $this->template['items'] = $this->items;
		
		// Load template variables into local symbol table
		extract($this->template);
		
		// Load template for requested mode, if it exists
		$templateForRequestedMode = $viewDir .'/'. $_JAG['requestedMode'] .'.php';
		$defaultTemplate = $viewDir . '/html.php';
		if (file_exists($templateForRequestedMode)) {
			// Template exists for requested mode; load it and declare mode
			include $templateForRequestedMode;
			$_JAG['mode'] = $_JAG['requestedMode'];
		} elseif (file_exists($defaultTemplate)) {
			// Template for requested mode doesn't exist; load default HTML template instead
			include $defaultTemplate;
			$_JAG['mode'] = 'html';
		}
		return true;
	}
	
	function GetRelatedArray($field) {
		if ($relatedModule = $this->schema[$field]['relatedModule']) {

			// Look for keyQuery.ini
			if ($relatedQueryParams = Module::ParseConfigFile($relatedModule, 'config/keyQuery.ini', true)) {

				// Fetch array using specified query
				$relatedQuery = new Query($relatedQueryParams);

			} elseif ($relatedTableInfo = Module::ParseConfigFile($relatedModule, 'config/table.ini', true)) {
				
				// Try to find a key field in related module table
				foreach ($relatedTableInfo as $field => $info) {
					if ($info['key'] == true) {
						$keyColumn = $field;
					}
				}
				
				// If we did find a key field, build query according to that
				if ($keyColumn) {
					$params = array();
					$params['fields'] = array('id', $keyColumn);
					$relatedQuery = new Query($params);
				}
			}
			
			// If we successfuly built a query, fetch data
			if ($relatedQuery) {
				$relatedQuery->AddFrom($relatedModule);
				return $relatedQuery->GetSimpleArray();
			}
			
		} elseif ($relatedArray = $this->schema[$field]['relatedArray']) {
			// Array is specified in a config file
			$relatedArrays = IniFile::Parse($this->modulePath .'config/relatedArrays.ini', true);
			$relatedData = $relatedArrays[$relatedArray];
			
			// Look for localized strings
			foreach ($relatedData as $key => $label) {
				if ($string = $this->strings[$relatedArray][$label]) {
					$relatedData[$key] = $string;
				}
			}
			return $relatedData;
		} else {
			return false;
		}
	}
	
	function DisplayForm($fieldsArray = false) {
		global $_JAG;
		
		if (!$this->schema) {
			// This module doesn't have a corresponding table
			return false;
		}
		
		// Create Form object
		$form = new Form();
		
		// Load existing values into form, if available
		
		if ($this->postData) {
			// Strip slashes before displaying data
			foreach ($this->postData as $key => $data) {
				$cleanArray[$key] = stripslashes($data);
			}
			$form->LoadValues($cleanArray);
		} elseif ($this->rawData[$this->itemID]) {
			$form->LoadValues($this->rawData[$this->itemID]);
		}
		
		// Load missing fields into form and display error, if applicable
		if ($this->missingData) {
			$form->LoadMissingFields($this->missingData);
			$errorString =
				$this->strings['form']['missingData'] ?
				$this->strings['form']['missingData'] :
				$_JAG['strings']['admin']['missingData'];
			$params = array('class' => 'errorMissing');
			$form->AddArbitraryData(e('p', $params, $errorString));
		}
		
		// Load invalid fields into form and display error, if applicable
		if ($this->invalidData) {
			$form->LoadInvalidFields($this->invalidData);
			$errorString =
				$this->strings['form']['invalidData'] ?
				$this->strings['form']['invalidData'] :
				$_JAG['strings']['admin']['invalidData'];
			$params = array('class' => 'errorInvalid');
			$form->AddArbitraryData(e('p', $params, $errorString));
		}
		
		// Display error if a file upload failed
		if ($this->fileUploadError) {
			/* FIXME: File upload error display is broken
			switch ($errorCode) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorSize'];
					break;
				case UPLOAD_ERR_PARTIAL:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorPartial'];
					break;
				default:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorUnknown'];
					break;
			}
			*/
			$errorString = $this->strings['form']['fileUploadError'] ? $this->strings['form']['fileUploadError'] : $_JAG['strings']['admin']['fileUploadError'];
			$params = array('class' => 'errorFileUpload');
			$form->AddArbitraryData(e('p', $params, $errorString));
		}

		foreach ($this->schema as $name => $info) {
			// Don't include basic module fields
			if ($_JAG['moduleFields'][$name]) {
				continue;
			}
			
			// Skip this item if $fieldsArray is present and it doesn't contain this item
			if ($fieldsArray && !in_array($name, $fieldsArray)) {
				continue;
			}
			
			// Get proper title from string
			if (!$title = $this->strings['form'][$name]) {
				// Use field name if no localized string is found
				$title = $name;
			}
			
			// Look for default value if this item has no value
			if (!isset($form->values[$name]) && isset($info['default'])) {
				$form->LoadValue($name, $info['default']);
			}
			
			// Use hidden field when 'hidden' value is true
			if ($info['hidden']) {
				$form->AddHidden($name);
				continue;
			}
			
			switch ($info['type']) {
				case 'string':
					$form->AddField($name, 40, $title);
					break;
				case 'password':
					if ($_JAG['user']->IsAdmin()) {
						// Show as regular field for user with admin privileges
						$form->AddField($name, 40, $title);
					} else {
						$form->AddPassword($name, 40, $title);
					}
					break;
				case 'lang':
					$languagesArray = $_JAG['strings']['languages'];
					$form->AddPopup($name, $languagesArray, $title);
					break;
				case 'shorttext':
					$form->AddField($name, 30, $title, 5);
					break;
				case 'text':
					$form->AddField($name, 30, $title, 22);
					break;
				case 'int':
				case 'signedint':
				case 'multi':
					// Display appropriate related data if available, else display a plain field
					if ($info['relatedModule'] || $info['relatedArray']) {
						if ($relatedData = $this->GetRelatedArray($name)) {
							if ($info['type'] == 'multi') {
								$form->AddMultipleSelect($name, $relatedData, $title);
							} else {
								$form->AddPopup($name, $relatedData, $title);
							}
						} else {
							$note = e('span', array('class' => 'disabled'), $_JAG['strings']['admin']['na']);
							$form->AddDisabled($name, $note, $title);
						}
					} else {
						$form->AddField($name, 5, $title);
					}
					break;
					
				case 'timestamp':
				case 'datetime':
					$form->AddDatetime($name, $title);
					break;
				case 'bool':
					$form->AddCheckbox($name, $title);
					break;
				case 'file':
					if ($form->values[$name]) {
						$form->AddHidden($name .'_id', $this->item[$name]->itemID);
						// A file has already been uploaded
						$inputParams = array(
							'id' => 'deleteFile_'. $name,
							'name' => 'deleteFile_'. $name,
							'type' => 'checkbox',
							'value' => 1
						);
						$checkbox = e('span', array('class' => 'fileDeleteCheckbox'), e('input', $inputParams) . $_JAG['strings']['admin']['deleteThisFile']);
						$filePath = $this->item[$name]->item['path'];
						switch ($this->item[$name]->item['type']) {
							case 'image/png':
							case 'image/jpeg':
							case 'image/gif':
								$image = i($filePath .'?context=adminThumbnail', $_JAG['strings']['admin']['thumbnail']);
								$fileLink = a($filePath, $image, array('class' => 'thumbnail'));
								break;
							default:
								$fileIcon = i('assets/images/admin_file.png', $_JAG['strings']['admin']['fileIcon']);
								$filePath = a($this->item[$name]->item['path'], $this->item[$name]->item['filename']);
								$fileLink = $fileIcon . $filePath;
								break;
						}
						$note = $checkbox . $fileLink;
					} else {
						// No file has been uploaded yet
						$note = $_JAG['strings']['admin']['noFile'];
					}
					$form->AddFile($name, $title, $note);
					break;
			}
		}

		$form->AddHidden('module', $this->name);
		
		$id = $this->item['master'] ? $this->item['master'] : $this->itemID;
		if ($id) {
			$form->AddHidden('master', $id);
			$action = 'edit';
		} else {
			$action = 'new';
		}
		
		// Determine submit button string
		$customString = $this->strings['form'][$this->parentModule->name .'.'. $action];
		if ($customString) {
			$submitString = $customString;
		} else {
			$submitString = $_JAG['strings']['admin'][$action];
		}
		
		$form->AddSubmit('update', $submitString);
		$form->Display();
	}

	function ValidateData() {
		
		// Get data from $_POST and make sure required data is present
		foreach ($this->schema as $field => $info) {
			// Collect data from $_POST
			if (isset($_POST[$field])) {
				$this->postData[$field] = $_POST[$field];
			}
			
			// Look for missing data
			if (array_key_exists($field, $_POST) && !$_POST[$field] && $info['required']) {
				$this->missingData[] = $field;
			}
			
			switch ($info['type']) {
				case 'bool':
					// Bool types need to be manually inserted
					if ($info['type'] == 'bool') {
						$this->postData[$field] = $_POST[$field] ? 1 : 0;
					}
					break;
				case 'datetime':
				case 'timestamp':
					// Reassemble datetime elements into a single string
					if (isset($_POST[$field .'_year'])) {
						$date['year'] = $_POST[$field .'_year'];
						$dateElements = array('month', 'day', 'hour', 'minutes', 'seconds');
						foreach ($dateElements as $element) {
							$date[$element] = Date::PadWithZeros($_POST[$field .'_'. $element]);
						}
						$dateString =
							$date['year'] .'-'. $date['month'] .'-'. $date['day'] .' '.
							$date['hour'] .':'. $date['minutes'] .':'. $date['seconds'];

						// Store values for each individual fields in case we don't have anything better
						foreach ($date as $element => $value) {
							$this->postData[$field .'_'. $element] = $value;
						}

						// Prepare and validate date
						$localDate = new Date($dateString, true);
						if ($localDate->isValid) {
							$databaseDate = $localDate->DatabaseTimestamp();
							$this->postData[$field] = $databaseDate;
						} else {
							$this->invalidData[] = $field;
						}
					}
					break;
				case 'file':
					// Add data from $_POST manually for files
					$this->postData[$field] = $_POST[$field .'_id'];
					
					// Look for file upload errors
					$errorCode = $_FILES[$field]['error'];
					
					// The 'no file' error should not trigger an error
					if ($errorCode && $errorCode != UPLOAD_ERR_NO_FILE) {
						$this->fileUploadError = $errorCode;
					}
					
					$file = $this->NestModule('files');
					
					// Check whether a file needs to be deleted
					if ($_POST['deleteFile_'. $field]) {
						$file->DeleteItem($_POST[$field .'_id']);
						$this->postData[$field] = 0;
					}
					
					// Make sure file was uploaded correctly
					if ($_FILES[$field]['error'] === 0) {
						// Update 'files' table
						$this->postData[$field] = $file->AddUploadedFile($field);
					}
					break;
			}
			
		}
	}
	
	function ProcessData() {
		global $_JAG;
		
		// Validate data; this fills $this->postData
		$this->ValidateData();
		
		// Display error and abort if there is invalid or missing data or a file upload error
		if ($this->invalidData || $this->missingData || $this->fileUploadError) {
			return false;
		}
		
		// Clear cache entirely; very brutal but will do for now
		$_JAG['cache']->Clear();

		// Run custom action method if available
		if ($action = $_POST['action']) {
			$actionMethod = $action . 'Action';
			if (method_exists($this, $actionMethod)) {
				$this->$actionMethod();
				return true;
			} elseif ($this->parentModule->name == 'admin') {
				// We're in admin mode; look for action in admin module
				if (method_exists($this->parentModule, $actionMethod)) {
					$this->parentModule->$actionMethod($this);
					return true;
				}
			}
		}

		// Determine what we need to insert from what was submitted
		foreach ($this->schema as $name => $info) {
			if (isset($this->postData[$name])) {
				// TODO - Ajouter affaire persmissions write access, genre
				// Exclude 'multi' fields; we handle them later
				if ($info['type'] != 'multi') {	
					$insertData[$name] = $this->postData[$name];
				}
			}
		}
		
		if (!$this->config['useCustomTable']) {
			// This is a standard table with special fields
			
			// If user is logged in, insert user ID
			if ($_JAG['user']->id) {
				$insertData['user'] = $_JAG['user']->id;
			}
			
			// If no language was given, insert default language
			if (!$this->postData['language']) {
				$insertData['language'] = $_JAG['defaultLanguage'];
			}
			
		}
		
		if (!$this->config['keepVersions']) {
			// Standard table; simple update
			
			if ($_POST['master']) {
				// Update mode
				$where = 'id = '. $_POST['master'];
				if (!$this->UpdateItems($insertData, $where)) {
					// Update failed
					trigger_error("Couldn't update module", E_USER_ERROR);
					return false;
				}
				$insertID = $_POST['master'];
			} else {
				// Post mode
				if (!$this->config['useCustomTable']) {
					$insertData['created'] = $_JAG['databaseTime'];
				}
				if (!Database::Insert($this->name, $insertData)) {
					trigger_error("Couldn't insert into module ". $this->name, E_USER_ERROR);
					return false;
				}
				
				// Keep ID of inserted item for path
				$insertID = Database::GetLastInsertID();
			}
		} else {
			// Special update for tables with multiple versions support
			
			// Set item as current
			$insertData['current'] = true;
			
			// If we already have a creation date and one wasn't specified, use that
			if (!$insertData['created'] && $this->item['created']) {
				$insertData['created'] = $this->item['created'];
			}
			
			if (!Database::Insert($this->name, $insertData)) {
				trigger_error("Couldn't insert into module ". $this->name, E_USER_ERROR);
			} else {
				// Keep ID of inserted item for path
				$insertID = Database::GetLastInsertID();
				
				// $this->postData now represents actual data
				$this->LoadData($this->postData);
	
				// Disable all other items with the same master
				if ($insertData['master']) {
					$updateParams['current'] = false;
					$whereArray = array(
						array(
							'master = '. $insertData['master'],
							'id = '. $insertData['master']
						),
						'id != '. $insertID
					);
					$where = Database::GetWhereString($whereArray);
					if (!Database::Update($this->name, $updateParams, $where)) {
						trigger_error("Couldn't update module ". $this->name, E_USER_ERROR);
						return false;
					}
				}
			}
			
		}
		
		// Update path
		$this->UpdatePath($insertID);
		
		// Get ID for this item
		$id = $_POST['master'] ? $_POST['master'] : $insertID;

		// Delete previous many-to-many relationships
		$where = array(
			'frommodule = '. $this->moduleID,
			'fromid = '. $insertID
		);
		if (!Database::DeleteFrom('_relationships', $where)) {
			trigger_error("Couldn't delete previous many-to-many relationships for module ". $this->name, E_USER_ERROR);
		}
		
		foreach ($this->schema as $name => $info) {
			switch ($info['type']) {
				case 'multi':
					// Insert many-to-many relationships
					foreach ($this->postData[$name] as $targetID) {
						// Insert each item into _relationships table
						$targetModuleName = $info['relatedModule'];
						$targetModuleID = array_search($targetModuleName, $_JAG['installedModules']);
						$params = array(
							'frommodule' => $this->moduleID,
							'fromid' => $insertID,
							'tomodule' => $targetModuleID,
							'toid' => $targetID
						);
						if (!Database::Insert('_relationships', $params)) {
							trigger_error("Couldn't insert many-to-many relationship for module ". $this->name, E_USER_ERROR);
						}
					}
					break;
			}
		}
		
		// Check whether we need to redirect to a specific anchor
		$anchor = $this->config['redirectToAnchor'][$this->parentModule->name];
		
		// Reload page
		HTTP::ReloadCurrentURL($anchor ? '#' . $anchor : '');
	}
	
	function UpdateItems($params, $where) {
		// Validate parameters
		foreach ($params as $field => $value) {
			if ($this->schema[$field]) {
				$validatedParams[$field] = $value;
			}
		}
		if (Database::Update($this->name, $validatedParams, $where)) {
			return true;
		} else {
			trigger_error("Couldn't update database", E_USER_WARNING);
			return false;
		}
	}
	
	function GetPath() {
		global $_JAG;
		
		// Only run this method if 'autoPaths' switch is set
		if (!$this->config['autoPaths']) return false;
		
		// Find key column
		foreach ($this->schema as $name => $info) {
			if ($info['key']) $keyColumn = $name;
		}
		if ($keyString = $this->item[$keyColumn]) {
			$parentPath = $this->config['path'][$_JAG['language']];
			return ($parentPath ? $parentPath : $this->name) .'/'. String::PrepareForURL($keyString);
		}
	}
	
	function UpdatePath($id = null) {
		// Check for GetPath() method
		if (!method_exists($this, 'GetPath')) return false;
		
		// Check whether we have data
		if (!$this->item) {
			// We don't; we need to fetch data
			$itemID = $_POST['master'] ? $_POST['master'] : $id;
			if (!$this->FetchItem($itemID)) {
				trigger_error("Couldn't fetch item data to update path", E_USER_ERROR);
				return false;
			}
		}
		
		// Run GetPath() method
		if ($path = $this->GetPath()) {
			$pathItemID = $_POST['master'] ? $_POST['master'] : $id;
			$safeInsert = $this->config['forbidObsoletePaths'] ? false : true;
			if ($insertedPath = Path::Insert($path, $this->moduleID, $pathItemID, $safeInsert)) {
				$this->item['path'] = $insertedPath;
				return true;
			} else {
				trigger_error("Couldn't insert path in database", E_USER_ERROR);
				return false;
			}
		} else {
			trigger_error("Couldn't update path because no path was returned", E_USER_WARNING);
			return false;
		}
	}
	
	function Revert($id) {
		/*
		// Determine master for this item
		$master = Query::SingleValue($this->name, 'master', 'id = '. $id);
		$master = $master ? $master : $id;
		*/
		$master = $_POST['master'];
		
		// Mark all versions of this item as non-current
		$params = array('current' => false);
		$where = array('id = '. $master .' OR master = '. $master);
		if (!$this->UpdateItems($params, $where)) {
			trigger_error("Failed to mark all versions of a module item as non-current", E_USER_ERROR);
			return false;
		}
		
		// Mark specified version of this item as current
		$params = array('current' => true);
		$where = array('id = '. $id);
		if ($this->UpdateItems($params, $where)) {
			// Update path
			$this->UpdatePath();
			return true;
		} else {
			trigger_error("Couldn't mark specified version of item as current", E_USER_ERROR);
			return false;
		}
	}
	
	function DeleteItem($master) {
		// Delete item
		if ($this->config['keepVersions']) {
			$where = 'id = '. $master .' OR master = '. $master;
		} else {
			$where = 'id = '. $master;
		}
		if (Database::DeleteFrom($this->name, $where)) {
			// Delete was successful; get rid of this item in _paths table
			if (Path::DeleteAll($this->moduleID, $master)) {
				return true;
			} else {
				trigger_error("Couldn't delete paths associated with deleted item", E_USER_ERROR);
				return false;
			}
			// Eventually, delete from _relationships where frommodule = this module
		} else {
			trigger_error("Couldn't delete module item from database", E_USER_ERROR);
			return false;
		}
	}
	
}

?>