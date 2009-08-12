<?php

class Jam {
	
	var $cache;
	var $filesDirectory;
	
	var $projectConfig;
	var $serverConfig;
	var $fieldTypes;
	var $versionsSupportFields;
	
	var $defaultLanguage;
	var $language;
	var $strings;	
	
	var $databaseLink;
	var $databaseTime;
	
	var $appModules;
	var $availableModules;
	var $installedModules;
	var $rootModuleName;
	var $rootModule;
	
	var $request;
	var $paths;
	var $adminPath;
	var $mode;
	var $user;
	var $contentType;
	
	var $title;
	var $template;
	
	function Initialize() {
		
		// Set PHP configuration options
		$options = array(
			'mbstring.language' => 'Neutral',
			'mbstring.internal_encoding' => 'UTF-8',
			'mbstring.encoding_translation' => 'On',
			'mbstring.http_input' => 'auto',
			'mbstring.detect_order' => 'ASCII,UTF-8,JIS,SJIS,EUC-JP',
			'upload_max_filesize' => '21M',
			'post_max_size' => '24M',
			'display_errors' => true
		);
		foreach ($options as $key => $value) {
			ini_set($key, $value);
		}

		// Required for UTF-8 support
		mb_language('uni');
		
		// Error display
		error_reporting(E_ALL^E_NOTICE);
		
		// Add app/ and engine/ to include path
		set_include_path(get_include_path() . PATH_SEPARATOR . './app' . PATH_SEPARATOR . './engine');

		// Load classes
		$classesDir = 'engine/classes';
		$dirHandle = opendir($classesDir);
		while ($filename = readdir($dirHandle)) {
			// Make sure files end in .php
			if ($filename != basename($filename, '.php')) {
				$classPath = $classesDir .'/'. $filename;
				if (!is_dir($classPath)) {
					require_once $classPath;
				}
			}
		}

		// Start caching engine; this also initializes output buffering
		$this->cache = new Cache();

		// Start output buffering
		ob_start('mb_output_handler');

		// Define files directory
		$this->filesDirectory = 'files/';

		// Load configuration files
		$this->projectConfig = IniFile::Parse('app/config/project.ini', true);
		$this->serverConfig = IniFile::Parse('app/config/server.ini', true);

		// Define constants
		define('ROOT', $this->serverConfig['root']);
		define('ERROR_FOREIGN_KEY_CONSTRAINT', 2); // Used in engine/classes/Module.php

		// Load database field types
		$this->fieldTypes = IniFile::Parse('engine/database/types.ini');

		// Load module fields
		$this->moduleFields = IniFile::Parse('engine/database/moduleFields.ini', true);
		$this->versionsSupportFields = IniFile::Parse('engine/database/versionsSupportFields.ini', true);

		// Load GetID3
		require_once('engine/libraries/getid3/getid3.php');

		// Determine default language
		$this->defaultLanguage = $this->projectConfig['languages'][0];

		// Determine requested language
		$requestedLanguage = $_GET['language'];
		if ($requestedLanguage && in_array($requestedLanguage, $this->projectConfig['languages'])) {
			// User has manually switched language
			Cookie::Create('language', $requestedLanguage);
			$this->language = $requestedLanguage;
		} elseif ($_COOKIE['language']) {
			// User has previously selected a language
			$this->language = $_COOKIE['language'];
		} else {
			// User has never selected a language; use default
			$this->language = $this->defaultLanguage;
		}

		// Load strings in the requested language
		$this->strings = IniFile::Parse('engine/strings/' . $this->language . '.ini', true);

		// Load shorthands (useful function aliases; must load after strings)
		require 'engine/helpers/shorthands.php';
		
		// Connect to database
		$db = $this->serverConfig['database'];
		if (!$this->databaseLink = Database::Connect($db['server'], $db['username'], $db['password'], $db['database'])) {
			trigger_error("Couldn't connect to database " . $db['database'], E_USER_ERROR);
		}

		// Get available modules list
		$engineModules = Filesystem::GetDirNames('engine/modules');
		$this->appModules = Filesystem::GetDirNames('app/modules');
		$this->availableModules = $engineModules + $this->appModules;

		// Strip root directory and trailing slashes from full path request
		$pathString = $_SERVER['REQUEST_URI'];
		$barePath = substr($pathString, 0, strrpos($pathString, '?'));
		$pathString = $barePath ? $barePath : $pathString;
		$fullRequest = rtrim($pathString, '/');
		preg_match('|^'. ROOT .'(.*)$|', $fullRequest, $requestArray);

		// Use requested URL, or use root path if none was requested
		$this->request = $requestArray[1] ? $requestArray[1] : $this->projectConfig['rootPath'];

		// We sync using database time (might differ from PHP time)
		$databaseTimeQuery = new Query(array('fields' => 'NOW()'));
		$databaseTime = $databaseTimeQuery->GetSingleValue();
		$this->databaseTime = $databaseTime;

		// Make sure everything is properly initialized
		$tables = Database::GetTables();
		if (!$tables['users'] || !$tables['_paths']) {
			$this->FirstRun();
		}
		
		// Get installed modules list
		$this->installedModules = Query::SimpleResults('_modules');

		// Create User object
		$this->user = new User();
		
		// Create layout object
		$this->template = new Template();
		
		// Determine mode
		$requestedMode = $_GET['mode'];
		$availableModes = IniFile::Parse('engine/config/modes.ini');
		if ($availableModes[$requestedMode]) {
			// If requested mode exists, use it
			$this->mode = $requestedMode;
		} else {
			// HTML is the default mode
			$this->mode = 'html';
		}

		// Load paths
		$paths = Query::FullResults('_paths');
		foreach ($paths as $path) {
			// Use path as key in $_JAM->paths array
			$this->paths[$path['path']] = $path;
		}

		// Look for request in paths
		if ($path = $this->paths[$this->request]) {
			// Path does exist
			if ($path['current']) {
				// This is a valid path; proceed to module
				if ($this->rootModule = Module::GetNewModule($this->installedModules[$path['module']], $path['item'])) {
					// Check whether we have sufficient privileges to display the module
					if ($this->rootModule->CanView()) {
						// Display module
						$this->rootModule->Display();

						// Determine path to admin pane for this item
						$adminPath = 'admin/'. $moduleName;
						if ($this->paths[$adminPath]) {
							if ($path['item']) {
								$adminPath .= '?a=edit&id='. $path['item'];
							}
							$this->adminPath = ROOT . $adminPath;
						} else {
							$this->adminPath = ROOT . 'admin';
						}
					} else {
						// Display login if we can't display
						$this->user->Connect();
					}
				} else {
					trigger_error("Couldn't load root module", E_USER_ERROR);
				}
			} else {
				// This is an obsolete URL; find its current (up to date) equivalent
				$whereArray = array(
					'module = '. $path['module'],
					'item = '. $path['item'],
					"language = '". $path['language'] ."'",
					'current = TRUE'
				);
				$currentPath = Query::SingleValue('_paths', 'path', $whereArray);
				HTTP::NewLocation($currentPath);
			}
		} else {
			// Path does not exist; throw 404
			header("HTTP/1.0 404 Not Found");
			$this->rootModule = Module::GetNewModule('errors');
			$this->rootModule->Display();
		}

		// Store and flush the contents of the output buffer
		$buffer = ob_get_clean();
		
		// Load and display template
		if ($this->mode == 'html' && $this->rootModuleName == 'admin') {
			// Special case for admin pages requested in HTML
			$templateName = 'html_admin';
		} else {
			$templateName = $this->mode;
		}
		$this->template->SetTemplate($templateName);
		$this->template->Display($buffer);

		// Set MIME type
		$contentType = $this->contentType ? $this->contentType : $availableModes[$this->mode];
		if ($contentType) {
			header('Content-Type: '. $contentType);
		}

		// Write to cache; this also cleans output buffering
		$this->cache->Write();
	}
	
	function FirstRun() {
		// Load table structure for required tables
		$tables = IniFile::Parse('engine/database/tables.ini', true);

		// Create tables
		foreach ($tables as $name => $schema) {
			if (!Database::CreateTable($name, $schema)) {
				trigger_error("Couldn't create table ". $name, E_USER_ERROR);
			}
		}

		// Manually add admin module to _modules table
		if (Query::TableIsEmpty('_modules')) {
			$adminModule = array('name' => 'admin');
			if (!Database::Insert('_modules', $adminModule)) {
				trigger_error("Couldn't install core modules", E_USER_ERROR);
			}
		}

		// Install required modules
		$requiredModules = array(
			'users',
			'files'
		);
		foreach ($requiredModules as $moduleName) {
			$module = Module::GetNewModule($moduleName);
			$module->Install();
		}

		// Add default admin user
		if (Query::TableIsEmpty('users')) {
			$adminUserParams = array(
				'created' => $this->databaseTime,
				'login' => 'admin',
				'name' => 'Admin',
				'password' => 'admin',
				'status' => 3
			);
			if (!Database::Insert('users', $adminUserParams)) {
				trigger_error("Couldn't create admin user", E_USER_ERROR);
			}
		}

		// Add admin path
		$adminModuleId = Query::SingleValue('_modules', 'id', "name = 'admin'");
		if (!Path::Insert('admin', $adminModuleId, false)) {
			trigger_error("Couldn't add admin path" , E_USER_ERROR);
		}

		// Redirect to admin interface
		HTTP::RedirectLocal('admin');
	}
	
	function AddTemplateVariable($name, $value) {
		$this->template->AddVariable($name, $value);
	}
	
}

?>