<?php

require_once('classes/Database.php');
require_once('classes/Date.php');
require_once('classes/Filesystem.php');
require_once('classes/Form.php');
require_once('classes/HTTP.php');
require_once('classes/Path.php');
require_once('classes/Layout.php');
require_once('classes/Template.php');
require_once('classes/Query.php');
require_once('classes/Query.php');

class Module {

	var $name;
	var $moduleID;
	var $itemID;
	var $config;
	var $schema;
	var $isRoot;

	var $modulePath;
	var $keyFieldName;

	var $hasFiles;
	var $hasMulti;
	var $hasLocalizedFields;
	var $isLocalizable;

	var $adminMenuStrings;
	var $strings;
	
	var $parentModule;
	
	var $items;
	var $item;
	var $rawData;
	var $processedData;
	
	var $postData = array();
	var $missingData;
	var $invalidData;
	var $fileUploadError;
	var $files;
	var $layout;
	var $view = array();
	
	/*
	 * Constructor
	 */
	
	function Module($name, $item = '') {
		global $_JAM;
		
		$this->name = $name;
		$this->itemID = $item;
		
		// Determine whether we are the root module
		if (!isset($_JAM->rootModuleName)) {
			$this->isRoot = true;
			$_JAM->rootModuleName = $this->name;
			
			// Create layout object
			$this->layout = new Layout();
		}
	}
	
	/*
	 * Static
	 */
	
	function DisplayNewModule($name, $item = '') {
		$module = Module::GetNewModule($name, $item);
		return $module->Display();
	}
	
	function GetNewModule($name, $item = '', $hasParent = false) {
		global $_JAM;
		
		if (!$_JAM->availableModules[$name]) {
			trigger_error("Couldn't create new module because '". $name ."' module does not exist", E_USER_ERROR);
		}
		$className = ucfirst($name) .'Module';
		$classPath = 'modules/'. $name .'/'. $className .'.php';
		if (Filesystem::FileExistsInIncludePath($classPath)) {
			// There is a custom module class; load it and create new instance
			require_once($classPath);
			$module = new $className($name, $item);
		} else {
			// There is no custom module class; use plain Module class
			$module = new Module($name, $item);
		}
		
		// Don't run FinishSetup() if module has parent; will run later in NestModule
		// FIXME: Kludgy.
		if (!$hasParent) {
			$module->FinishSetup();
		}
		return $module;
	}
	
	function ParseConfigFile($moduleName, $iniFile, $processSections = false) {
		global $_JAM;
		
		// Determine whether requested module is a custom (app-specific) or engine module
		$iniFileRoot = in_array($moduleName, $_JAM->appModules) ? 'app' : 'engine';
		
		// Build path to config file
		$iniFilePath = $iniFileRoot .'/modules/'. $moduleName .'/'. $iniFile;
		
		return IniFile::Parse($iniFilePath, $processSections);
	}
	
	/*
	 * Static private
	 */
	
	function GetAdminMenuString($module) {
		global $_JAM;
		$config = Module::ParseConfigFile($module, 'config/config.ini');
		$strings = Module::ParseConfigFile($module, 'strings/'. $_JAM->language .'.ini');
		if ($config['hideFromAdmin']) {
			// Module asks not to show up in menu
			return false;
		} elseif ($module == 'users' && $_JAM->projectConfig['singleUser']) {
			// Project is single-user, and we don't want the "users" module to show up in the admin interface
			return false;
		} elseif ($config['canView'] && !$_JAM->user->HasPrivilege($config['canView'])) {
			// User doesn't have sufficient privileges to view the module
			return false;
		} elseif ($string = $strings['adminTitle']) {
			return $string;
		} else {
			return $module;
		}
	}
	
	function InsertTableNames ($array, $oldName, $newName) {
		// Recursive function to process custom parameters
		if ($array) {
			foreach ($array as $key => $value) {
				if (is_string($value)) {
					$returnArray[$key] = preg_replace('/^'. $oldName .'$/', $newName, $value);
				} elseif (is_array($value)) {
					$returnArray[$key] = Module::InsertTableNames($value, $oldName, $newName);
				} else  {
					$returnArray[$key] = $value;
				}
			}
			return $returnArray;
		} else {
			return false;
		}
	}
	
	
	/*
	 * Private
	 */
	
	function FinishSetup() {
		global $_JAM;

		// Check whether this is an app-level or engine-level module
		$modulePathRoot = in_array($this->name, $_JAM->appModules) ? 'app' : 'engine';
		$this->modulePath = $modulePathRoot .'/modules/'. $this->name .'/';
		
		// Make sure this module exists
		if (!$_JAM->availableModules[$this->name]) {
			return false;
		}
		
		// Load configuration files
		$this->config = IniFile::Parse($this->modulePath .'config/config.ini', true);
		$this->strings = IniFile::Parse($this->modulePath .'strings/'. $_JAM->language .'.ini', true);
		
		// Check whether we should disable cache
		if ($this->config['disableCache']) {
			$_JAM->cache->Forbid();
		}
		
		// Get info for this module's table, if there is one
		if ($schema = IniFile::Parse($this->modulePath .'config/schema.ini', true)) {
			
			// Determine key field
			if (!$this->keyFieldName = $this->config['keyField']) {
				// If config file didn't specify a key field, use the first field
				reset($schema);
				$this->keyFieldName = key($schema);
			}
			
			// Merge with standard basic fields if applicable
			if ($this->config['useCustomTable']) {
				$this->schema = $schema;
			} else {
				$this->schema = $_JAM->moduleFields;
				if ($this->config['keepVersions']) {
					// Additional fields are needed for versions support
					$this->schema += $_JAM->versionsSupportFields;
				}
				$this->schema += $schema;
			}

			foreach ($this->schema as $name => $info) {
				
				// Determine whether we have localized fields
				if ($info['localizable']) {
					$this->isLocalizable = true;
				}
				
				// Look for specific field types
				switch ($info['type']) {
					case 'file':
						$this->hasFiles = true;
						break;
					case 'multi':
						$this->hasMulti = true;
						$relatedModuleName = $info['relatedModule'];
						$relatedModuleID = array_search($relatedModuleName, $_JAM->installedModules);
						$this->multiRelatedModules[$relatedModuleID] = $name;
						break;
				}
			}
		}
		
		// Make sure module is installed and get ID for this module
		if ($this->moduleID = @array_search($this->name, $_JAM->installedModules)) {
			
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
				if ($this->Initialize() == false) return false;
			}
		}
		
	}
	
	function Install() {
		global $_JAM;
		
		// Make sure table has not already been installed
		if ($installedModules = Query::SimpleResults('_modules')) {
			$_JAM->installedModules = $installedModules;
			if (in_array($this->name, $_JAM->installedModules)) {
				return true;
			}
		}
		
		// Determine whether we need a table at all
		if ($this->schema) {
			foreach ($this->schema as $name => $info) {
				// Split fields between main table and localized table
				if ($info['localizable']) {
					$localizedTableSchema[$name] = $info;
				} else {
					$mainTableSchema[$name] = $info;
				}
				
				// Check whether we need to install other modules first
				if (
					($relatedModule = $info['relatedModule']) &&
					!in_array($this->name, $_JAM->installedModules) &&
					$relatedModule != $this->name
				) {
					$module = Module::GetNewModule($relatedModule);
					$module->Install();
				}
			}
			
			// Create main table
			if ($mainTableSchema) {
				if (!Database::CreateTable($this->name, $mainTableSchema)) {
					trigger_error("Couldn't create table for module ". $this->name, E_USER_ERROR);
					return false;
				}
				
				// If localized fields were found, we need a localized table
				if ($localizedTableSchema) {
					$baseFields = IniFile::Parse('engine/database/localizedTableFields.ini', true);
					$localizedTableSchema = $baseFields + $localizedTableSchema;
					if (!Database::CreateTable($this->name .'_localized', $localizedTableSchema)) {
						trigger_error("Couldn't create localized table for module ". $this->name, E_USER_ERROR);
						return false;
					}
				}
			}
			
		}
		
		// Add entry to _modules table
		$params = array('name' => $this->name);
		if (Database::Insert('_modules', $params)) {
			// Get ID of the row we just inserted
			$this->moduleID = Database::GetLastInsertID();
			
			// Add admin path to _paths table FIXME: Untested
			$adminModuleID = array_search('admin', $_JAM->installedModules);
			if (!Path::Insert('admin/'. $this->name, $adminModuleID, $this->moduleID)) {
				trigger_error("Couldn't add admin path for module ". $this->name, E_USER_ERROR);
				return false;
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
	
	function CanView() {
		global $_JAM;
		if ($this->config['canView']) {
			return $_JAM->user->HasPrivilege($this->config['canView']);
		} else {
			return true;
		}
	}
	
	function CanInsert() {
		global $_JAM;
		if ($this->config['canInsert']) {
			return $_JAM->user->HasPrivilege($this->config['canInsert']);
		} else {
			return true;
		}
	}
	
	function CanDelete() {
		global $_JAM;
		if ($this->config['canDelete']) {
			return $_JAM->user->HasPrivilege($this->config['canDelete']);
		} else {
			return true;
		}
	}
	
	function NestModule($name, $item = '') {
		$module = Module::GetNewModule($name, $item, true);
		$module->AttachParent($this);
		$module->FinishSetup();
		return $module;
	}
	
	function AttachParent(&$parentModule) {
		$this->parentModule =& $parentModule;
	}
	
	function DisplayNestedModule($name, $item = '') {
		$module = $this->NestModule($name, $item);
		$module->Display();
	}
	
	function FetchItem($id) {
		global $_JAM;
		
		if ($this->config['keepVersions']) {
			$where = '('. $this->name .'.master = '. $id .' OR '.
				$this->name .'.id = '. $id .' AND '. $this->name .'.master IS NULL)';
		} else {
			$where = $this->name .'.id = '. $id; 
		}
		
		$params = array(
			'where' => $where,
			'limit' => 1
		);
		
		if ($items = $this->FetchItems($params)) {
			$this->itemID = $id;
			return $this->item = current($items);
		} else {
			return false;
		}
	}
	
	function FetchItems($queryParams = '') {
		global $_JAM;
		
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
		
		/*
		// Order by master if we're keeping versions
		if ($this->config['keepVersions']) {
			$query->AddOrderBy('master DESC');
		}*/
		
		// Add localized data
		if ($this->isLocalizable) {
			$localizedTable = $this->name .'_localized';
			$query->AddFields(array('language' => $localizedTable .'.language'));
			$query->AddFrom($localizedTable);
			$where = array(
				$localizedTable .'.item = '. $this->name .'.id',
				$localizedTable .".language = '". $_JAM->language ."'"
			);
			$query->AddWhere($where);
		}
		
		// Load all fields if none were specified
		if (!$queryParams['fields']) {
			foreach($this->schema as $name => $info) {
				$queryParams['fields'][] = $name;
			}
		}
		
		foreach($this->schema as $name => $info) {
			// Manually remove multi fields from query; they will be processed anyway (possibly kludgy)
			if ($info['type'] == 'multi') {
				if ($multiFieldKey = array_search($name, $queryParams['fields'])) {
					unset($queryParams['fields'][$multiFieldKey]);
				}
			}

			// Process custom parameters
			if ($info['localizable']) {
				$replaceString = $this->name .'_localized.'. $name;
			} else {
				$replaceString = $this->name .'.'. $name;
			}
			
			// Fetch data for related modules
			if (@in_array($name, $queryParams['fields'])) {
				if (
					$info['type'] == 'int' && 
					($relatedModule = $info['relatedModule']) &&
					$relatedModule != 'users' &&
					$relatedModule != $this->name
				) {
					// Add fields from foreign module
					$relatedModuleSchema = Module::ParseConfigFile($relatedModule, 'config/schema.ini', true);
					foreach($relatedModuleSchema as $foreignName => $foreignInfo) {
						$fields[$name .'_'. $foreignName] = $relatedModule .'.'. $foreignName;
					}
					$query->AddFields($fields);
					
					// Determine whether we should look for 'master' or 'id' field
					$relatedModuleConfig = Module::ParseConfigFile($relatedModule, 'config/config.ini', true);
					$joinCondition = $this->name .'.'. $name .' = ';
					if ($relatedModuleConfig['keepVersions']) {
						$joinCondition .= $relatedModule .'.master AND '. $relatedModule .'.current = TRUE';
					} else {
						$joinCondition .= $relatedModule .'.id';
					}
					
					// Build query
					$joinTable = $relatedModule;
					$query->AddJoin($this->name, $joinTable, $joinCondition);
				}
			}
			$queryParams = Module::InsertTableNames($queryParams, $name, $replaceString);
		}
		
		// Load custom parameters
		$query->LoadParameters($queryParams);
		
		// Load paths if appropriate
		if ($this->config['autoPaths'] || (get_parent_class($this) && method_exists($this, 'GetPath'))) {
			$query->AddFields(array('path' => '_paths.path'));
			$joinTable = '_paths';
			$joinConditions[] = '_paths.module = '. $this->moduleID;
			$joinConditions[] = '_paths.current = 1';
			if ($this->config['keepVersions']) {
				$joinConditions[] = '((_paths.item = '. $this->name .'.id AND '. $this->name .'.master IS NULL) OR '.
				'_paths.item = '. $this->name .'.master)';
			} else {
				$joinConditions[] = '_paths.item = '. $this->name .'.id';
			}
			$query->AddJoin($this->name, $joinTable, $joinConditions);
			if ($this->isLocalizable) {
				$query->AddWhere($this->name . '_localized.language = _paths.language');
			}
		}
		
		// Debug query:
		//dp($query->GetQueryString());
		
		// Fetch actual module data
		if ($this->rawData = $query->GetArray()) {
			// Load data for 'multi' fields
			if ($this->hasMulti) {
				$where = 'frommodule = '. $this->moduleID;
				if ($multiArray = Query::FullResults('_relationships', $where)) {
					foreach($this->rawData as $id => $item) {
						foreach($multiArray as $multiData) {
							if($multiData['fromid'] == $id) {
								$this->rawData[$id][$this->multiRelatedModules[$multiData['tomodule']]][] = $multiData['toid'];
							}
						}
					}
				}
			}
			
			// Make a copy of the data for processing so we can keep the raw data available
			$this->processedData = $this->rawData;

			// Post-process data
			foreach($this->schema as $name => $info) {
				foreach ($this->processedData as $id => $data) {
					if ($this->processedData[$id][$name]) {
						switch ($info['type']) {
							case 'string':
								$this->processedData[$id][$name] = TextRenderer::SmartizeText($data[$name]);
								break;
							case 'text':
							case 'shorttext':
								if (!$info['wysiwyg']) {
									// Render text using TextRenderer if it's not a WYSIWYG field
									if (strstr($data[$name], "\n") !== false) {
										// String contains newline characters; format as multiline text
										$this->processedData[$id][$name] = TextRenderer::TextToHTML($data[$name]);
									} else {
										// String is a single line; format as single line
										$this->processedData[$id][$name] = TextRenderer::SmartizeText($data[$name]);
									}
								}
								break;
							case 'datetime':
							case 'timestamp':
							case 'date':
							case 'time':
								$this->processedData[$id][$name] = new Date($data[$name]);
								break;
							case 'file':
								$this->processedData[$id][$name] = $this->NestModule('files', $data[$name]);
								break;
						}
					}
				}
			}
			
			// Subclasses can provide a method to further format data
			if (method_exists($this, 'FormatData')) {
				$this->FormatData();
			}
			
			if ($this->items) {
				// If $this->items is already set, don't overwrite it
				return $this->processedData;
			} else {
				return $this->items = $this->processedData;
			}
		} else {
			return false;
		}

	}
	
	function LoadData($data) {
		return $this->item = $data;
	}
	
	function SetLayout($name) {
		global $_JAM;
		$this->layout->SetLayout($name .'.'. $_JAM->mode);
	}
	
	function Display() {
		global $_JAM;
		
		// Start output buffering
		ob_start('mb_output_handler');
		
		// Determine layout
		if (isset($this->layout)) {
			$layoutName = $this->config['layout'];
			if (!$layoutName) $layoutName = 'default';
			$layoutFile = $layoutName .'.'. $_JAM->mode;
			$this->layout->SetLayout($layoutFile);
		}
		
		// Determine whether we're a nested module
		if ($this->parentModule->name) {
			// Try to load view bearing parent module's name
			$viewDidLoad = $this->LoadView($this->parentModule->name);
		}
		
		// Determine whether we're looking at a single item in the module
		if (!$viewDidLoad && $this->itemID) {
			$viewDidLoad = $this->LoadView('item');
		}
		
		if (!$viewDidLoad) {
			$this->LoadView('default');
		}
		
		// Wrap into layout if we're the root module
		$buffer = ob_get_clean();
		if ($this->isRoot) {
			$this->layout->Display($buffer);
		} else {
			print $buffer;
		}
	}
	
	function LoadView($view) {
		global $_JAM;
		
		// Make sure we have sufficient privileges
		if (!$this->CanView()) {
			return false;
		}
		
		// Make sure a view was specified
		if (!$view) {
			return false;
		}
		
		// Try to load module data if an item ID is specified
		if ($this->schema && $this->itemID && !$this->items) {
			$this->FetchItem($this->itemID);
		}
		
		// Set a few variables that we'll need later
		$controllerMethodSuffix = 'ViewController';
		$moduleViewsDir = $this->modulePath .'views/';
		$viewFileSuffix = '.'. $_JAM->mode . '.php';

		// Determine controller method name
		$controllerMethod = ucfirst($view) . $controllerMethodSuffix;

		// Check whether we're in admin mode; that's a special case
		if ($_JAM->rootModuleName == 'admin') {
			// Run controller method if available
			$adminControllerMethod = 'Admin'. $controllerMethod;
			if (method_exists($this, $adminControllerMethod)) {
				$this->$adminControllerMethod();
				$methodDidRun = true;
			} elseif (method_exists($_JAM->rootModule, $adminControllerMethod)) {
				$_JAM->rootModule->$adminControllerMethod();
				$methodDidRun = true;
			}
			
			// Determine path to view file
			$viewFilename = 'admin_'. $view . $viewFileSuffix;
			$moduleViewPath = $moduleViewsDir . $viewFilename;
			$adminViewPath = $_JAM->rootModule->modulePath .'views/'. $viewFilename;
			if (file_exists($moduleViewPath)) {
				$viewPath = $moduleViewPath;
			} elseif (file_exists($adminViewPath)) {
				$viewPath = $adminViewPath;
			}
		} else {
			// Run controller method if available
			if (method_exists($this, $controllerMethod)) {
				$this->$controllerMethod();
				$methodDidRun = true;
			}
			
			// Determine path to view file
			$requestedViewPath = $moduleViewsDir . $view . $viewFileSuffix;
			if (file_exists($requestedViewPath)) {
				$viewPath = $requestedViewPath;
			}
		}
		
		// If no controller method has run and no view file has been found, abort process
		if (!$methodDidRun && !$viewPath) {
			return false;
		}
		
		// Load module data into view variables
		if ($this->item) {
			// If we have a single item, load directly into local symbol table
			extract($this->item);
		}
		if ($this->items) {
			// If we have multiple items, load as an array into $items
			$this->view['items'] = $this->items;
		}
		
		// Load view variables into local symbol table
		extract($this->view);
		
		// Include view file
		if ($viewPath) {
			include $viewPath;
		}

		return true;
	}
	
	function LoadViewInLayoutVariable($view) {
		global $_JAM;
		
		ob_start('mb_output_handler');
		$this->LoadView($view);
		$viewContent = ob_get_clean();
		
		// Add to layout
		$this->layout->AddVariable($view, $viewContent);
		
		// Add to template
		$_JAM->AddTemplateVariable($view, $viewContent);
	}
	
	function GetRelatedArray($field) {
		if ($relatedModule = $this->schema[$field]['relatedModule']) {

			$relatedModuleConfig = Module::ParseConfigFile($relatedModule, 'config/config.ini', true);

			// Look for keyQuery.ini
			if ($relatedQueryParams = Module::ParseConfigFile($relatedModule, 'config/keyQuery.ini', true)) {

				// Fetch array using specified query
				$relatedQuery = new Query($relatedQueryParams);
				
			} else {
				if (!$keyField = $relatedModuleConfig['keyField']) {
					// If no key field was specified in config file, use first field
					$relatedModuleSchema = Module::ParseConfigFile($relatedModule, 'config/schema.ini', true);
					reset($relatedModuleSchema);
					$keyField = key($relatedModuleSchema);
				}

				// If we do find a key field, build query according to that
				if ($keyField) {
					$params = array();
					if ($relatedModuleConfig['keepVersions']) {
						$params['fields']['id'] = 'IF(master IS NULL, id, master)';
					} else {
						$params['fields']['id'] = 'id';
					}
					$params['fields'][] = $keyField;
					$relatedQuery = new Query($params);
				}
			}
			
			// If we successfuly built a query, fetch data
			if ($relatedQuery) {
				$relatedQuery->AddFrom($relatedModule);
				
				// Manage versions
				if ($relatedModuleConfig['keepVersions']) {
					$relatedQuery->AddWhere($relatedModule .'.current = TRUE');
				}
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
	
	function GetForm() {
		if (!$this->schema) {
			// This module doesn't have a corresponding table
			return false;
		}
		
		// Create Form object
		return new ModuleForm($this);
	}
	
	function AutoForm($fieldsArray = false, $hiddenFields = false) {
		global $_JAM;
		
		// Create Form object
		if (!$form = $this->GetForm()) return false;
		$form->Open();
		
		// Include language selection menu if applicable
		if (
			!$this->config['languageAgnostic'] &&
			(count($_JAM->projectConfig['languages']) > 1) &&
			!($fieldsArray && !@in_array('language', $fieldsArray))
		) {
			if ($this->item) {
				print $form->Hidden('language');
			} else {
				foreach ($_JAM->projectConfig['languages'] as $language) {
					$languagesArray[$language] = $_JAM->strings['languages'][$language];
				}
				print $form->Popup('language', $languagesArray, $_JAM->strings['fields']['language']);
			}
		}
		
		// Include sortIndex field if applicable
		if ($this->config['allowSort']) {
			print $form->Field('sortIndex', '3', $_JAM->strings['fields']['sortIndex']);
		}
		
		foreach ($this->schema as $name => $info) {
			// Don't include basic module fields
			if (!$this->config['useCustomTable'] && $_JAM->moduleFields[$name]) {
				continue;
			}
			
			// Don't include versions support fields
			if ($this->config['keepVersions'] && $_JAM->versionsSupportFields[$name]) {
				continue;
			}
			
			// Skip this item if $fieldsArray is present and it doesn't contain this item
			if ($fieldsArray && !in_array($name, $fieldsArray)) {
				continue;
			}
			
			// Get proper title from string
			if (!$title = $this->strings['fields'][$name]) {
				// Use field name if no localized string is found
				$title = $name;
			}
			
			print $form->AutoItem($name, $title);
		}
		
		// Display related modules if we have item data
		if ($this->itemID) {
			foreach ($_JAM->installedModules as $module) {
				// First check whether we have a view configured for related module display
				if (
					($relatedModuleSchema = Module::ParseConfigFile($module, 'config/schema.ini', true)) &&
					($relatedModuleKeyQuery = Module::ParseConfigFile($module, 'config/keyQuery.ini', true))
				) {
					foreach($relatedModuleSchema as $field => $info) {
						if ($info['relatedModule'] == $this->name) {
 							$relatedModule = Module::GetNewModule($module);
							// Load all fields
							$queryParams = array(
								'fields' => $relatedModuleKeyQuery['fields'],
								'where' => $module .'.'. $field .' = '. $this->itemID
							);
							$relatedModule->FetchItems($queryParams);
							$relatedModule->LoadView('subform');
						}
					}
				}
			}
		}
		
		if ($hiddenFields) {
			foreach ($hiddenFields as $field => $value) {
				print $form->Hidden($field, $value);
			}
		}

		print $form->Submit();
		$form->Close();
		return true;
	}

	function ValidateData() {
		// First check whether we have sufficient privileges to insert
		if (!$_POST['master']) {
			// We're inserting, not updating
			if (!$this->CanInsert()) {
				trigger_error("Insufficient privileges to insert into module ". $this->name, E_USER_ERROR);
				return false;
			}
		}
		
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
				case 'date':
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
				case 'time':
					$hour = $_POST[$field .'_hour'];
					$minutes = $_POST[$field .'_minutes'];
					$timeString = $hour .':'. $minutes;

					// Store values for both individual fields in case we don't have anything better
					$this->postData[$field .'_hour'] = $hour;
					$this->postData[$field .'_minutes'] = $minutes;
					
					// Prepare and validate date
					if (Date::ValidateTime($timeString)) {
						// Warning: Time is not localized according to database time
						$this->postData[$field] = $timeString;
					} else {
						$this->invalidData[] = $field;
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
					
					// Add 'files' module if it doesn't exist
					if (!$this->files) {
						$this->files = $this->NestModule('files');
					}
					
					// Check whether a file needs to be deleted
					if ($_POST['deleteFile_'. $field]) {
						$this->files->DeleteItem($_POST[$field .'_id']);
						$this->postData[$field] = 0;
					}
					
					// Make sure file was uploaded correctly
					if ($_FILES[$field]['error'] === 0) {
						// Update 'files' table
						$this->postData[$field] = $this->files->AddUploadedFile($field);
					}
					break;
			}
		}
		
		// Language field should be included as well
		if (isset($_POST['language'])) {
			$this->postData['language'] = $_POST['language'];
		}
	}
	
	function ProcessData() {
		global $_JAM;
		
		// Validate data; this fills $this->postData
		$this->ValidateData();
		
		// Display error and abort if there is invalid or missing data or a file upload error
		if ($this->invalidData || $this->missingData || $this->fileUploadError) {
			return false;
		}
		
		// Clear cache entirely; very brutal but will do for now
		$_JAM->cache->Clear();
		
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
			// Omit fields which we can't edit
			if ($info['canEdit'] && !$_JAM->user->HasPrivilege($info['canEdit'])) {
				continue;
			}
			
			// Make sure data exists, and exclude 'multi' fields; we handle them later
			if (isset($this->postData[$name]) && $info['type'] != 'multi') {
				if ($info['localizable']) {
					$localizedData[$name] = $this->postData[$name];
				} else {
					$insertData[$name] = $this->postData[$name];
				}
			}
		}
		
		if (!$_GET['item']) { // FIXME: More kludge! Translations again.
			if (!$this->config['useCustomTable']) {
				// This is a standard table with special fields

				// If user is logged in, insert user ID
				if ($_JAM->user->id) {
					$insertData['user'] = $_JAM->user->id;
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
						$insertData['created'] = $_JAM->databaseTime;
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
		} else {
			// FIXME: Kuldgy. Added to make translations work.
			$insertID = $_GET['item'];
		}
		
		// Insert localized data
		if ($localizedData) {
			$tableName = $this->name .'_localized';
			$localizedData['item'] = $insertID;
			$localizedData['language'] = $this->postData['language'];
			$where = array('item = '. $insertID, "language = '". $localizedData['language'] ."'");
			if (Database::Update($tableName, $localizedData, $where)) {
				// Insert if no rows were affected
				if (Database::GetModifiedRows() == 0) {
					if (Database::Insert($tableName, $localizedData)) {
						$success = true;
					} else {
						trigger_error("Couldn't insert localized data for module ". $this->name, E_USER_ERROR);
					}
				} else {
					$success = true;
				}
				
				// Put data into module object to reflect changes in the database
				if ($success) {
					$this->LoadData($localizedData);
				}
			} else {
				trigger_error("Couldn't update localized data for module ". $this->name, E_USER_ERROR);
				return false;
			}
		}
		
		if ($insertID) {
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
							$targetModuleID = array_search($targetModuleName, $_JAM->installedModules);
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
		}
		
		if (method_exists($this, 'PostProcessData')) {
			$this->PostProcessData($insertID);
		}
		
		// Check whether we need to redirect to a specific anchor
		$anchor = $this->config['redirectToAnchor'][$this->parentModule->name];
		
		// Reload page
		if ($_JAM->rootModuleName == 'admin' || !$this->config['postSubmitRedirect']) {
			HTTP::ReloadCurrentURL('?m=updated'. ($anchor ? '#' . $anchor : ''));
		} else {
			HTTP::RedirectLocal($this->config['postSubmitRedirect']);
		}
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
		global $_JAM;
		
		// Only run this method if 'autoPaths' switch is set
		if (!$this->config['autoPaths']) return false;
		
		if ($keyString = $this->item[$this->keyFieldName]) {
			$parentPath = $this->config['path'][$_JAM->language];
			return ($parentPath ? $parentPath : $this->name) .'/'. String::PrepareForURL($keyString);
		} else {
			trigger_error("Couldn't get path; probably lacking item data in module object", E_USER_ERROR);
		}
	}
	
	function UpdatePath($id = null) {
		global $_JAM;
		
		// Check whether we have data
		if (!$this->item) {
			// We don't; we need to fetch data
			$itemID = $_POST['master'] ? $_POST['master'] : $id;
			// FIXME: Again, fucking kludge for translations.
			if ($_POST['language']) {
				$originalLanguage = $_JAM->language;
				$_JAM->language = $_POST['language'];
			}
			if (!$this->FetchItem($itemID)) {
				return false;
			}
			if ($originalLanguage) $_JAM->language = $originalLanguage;
		}
		
		$safeInsert = $this->config['forbidObsoletePaths'] ? false : true;

		// Update path for module item
		if ($path = $this->GetPath()) {
			$pathItemID = $_POST['master'] ? $_POST['master'] : $id;
			$language = $_POST['language'];
			if ($insertedPath = Path::Insert($path, $this->moduleID, $pathItemID, $safeInsert, $language)) {
				$this->item['path'] = $insertedPath;
			} else {
				trigger_error("Couldn't insert path in database", E_USER_ERROR);
				return false;
			}
		}
		
		// Update path for files
		if ($this->files) {
			foreach ($this->schema as $name => $info) {
				if ($info['type'] == 'file') {
					if (!is_object($this->item[$name])) {
						$this->item[$name] = $this->NestModule('files', $this->item[$name]);
					}
					if ($filePath = $this->item[$name]->GetPath($name)) {
						if (!Path::Insert($filePath, $this->files->moduleID, $this->item[$name]->itemID, $safeInsert)) {
							trigger_error("Couldn't insert path for file associated with field ". $name ." in module ". $this->name, E_USER_ERROR);
						}
					}
				}
			}
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
		// First make sure we have sufficient privileges
		if (!$this->CanDelete()) {
			trigger_error("Insufficient privileges to delete from module ". $this->name, E_USER_ERROR);
			return false;
		}
		
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
			if (Database::GetErrorNumber() == 1451) {
				return ERROR_FOREIGN_KEY_CONSTRAINT;
			} else {
				trigger_error("Couldn't delete module item from database", E_USER_ERROR);
				return false;
			}
		}
	}
	
}

?>