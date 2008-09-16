<?php

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
		'created' => $_JAG['databaseTime'],
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
?>
