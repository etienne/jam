<?php

class UsersModule extends Module {
	
	function LoginAction() {
		// Validate login
		$where = array(
			"login = '". $_POST['login'] ."'",
			"password = '". $_POST['password'] ."'"
		);
		
		if ($userInfo = Query::SingleRow($this->name, $where)) {
			// Login succeeded; create cookie and reload this page
			$_JAM->user->Login($userInfo['id']);
			HTTP::ReloadCurrentURL();
		} else {
			// Login failed
		}
	}
	
}

?>
