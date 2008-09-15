<?php

class AdminModule extends Module {
	
	function Initialize() {
		global $_JAG;
		
		// Install modules that require installation
		$modulesInstalled = false;
		foreach ($_JAG['availableModules'] as $moduleName) {
			if (!in_array($moduleName, $_JAG['installedModules'])) {
				// Module needs to be installed
				$module = Module::GetNewModule($moduleName);
				if (!$module->Install()) {
					trigger_error("Couldn't install module " . $moduleName);
					return false;
				}
				$modulesInstalled = true;
			}
		}
		
		// Reload this page if we installed any modules
		if ($modulesInstalled) {
			HTTP::ReloadCurrentURL();
		}
		
		return true;
	}
	
	function Display() {
		global $_JAG;
		// Load correct view
		if ($this->itemID) {
			// $this->itemID contains the ID of the requested module in the _modules table
			$moduleName = $_JAG['installedModules'][$this->itemID];
			
			// Add requested module
			// Note: $_GET['id'] is not necessarily set
			$module = $this->NestModule($moduleName, $_GET['id']);

			if (!$module->LoadView($_GET['a'])) {
				$module->LoadView('default');
			}
		}
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
