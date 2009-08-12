<?php

require_once('classes/Form.php');
require_once('classes/Cookie.php');
require_once('classes/Query.php');

class User {
	
	/*
	 * User status codes are:
	 *    0 - guest (anonymous visitor)
	 *    1 - user (registered user)
	 *    2 - webmaster (some administration control)
	 *    3 - admin (full administration privileges)
	 */
	
	var $id;
	var $name;
	var $status = 0;  // Guest access by default
	var $classes = array(
		'guest' => 0,
		'user' => 1,
		'webmaster' => 2,
		'admin' => 3
	);
	
	/*
	 * Constructor
	 */
	
	function User() {
		$id = $this->DecodeID($_COOKIE['id']);
		$this->FetchData($id);
		
		// Logout if requested
		if ($_GET['a'] == 'logout') {
			if (!$this->Logout()) {
				trigger_error("Couldn't log out", E_USER_ERROR);
			}
		}
		
	}
	
	/*
	 * Public
	 */
	
	function HasPrivilege($class) {
		if ($this->classes[$class] && $this->status >= $this->classes[$class]) {
			return true;
		} else {
			return false;
		}
	}
	
	function IsLoggedIn() {
		return ($this->status > 0) ? true: false;
	}
	
	function IsWebmaster() {
		return ($this->status > 1) ? true: false;
	}
	
	function IsAdmin() {
		return ($this->status > 2) ? true: false;
	}
	
	function FetchData($id) {
		if ($userInfo = Query::SingleRow('users', 'id = '. $id)) {
			$this->id = $id;
			$this->name = $userInfo['name'];
			$this->status = $userInfo['status'];
			return true;
		} else {
			return false;
		}
	}
	
	function Connect() {
		global $_JAM;
		// Check whether user has submitted the login form
		if ($_POST['connect']) {
			// Validate login
			$where = array(
				"login = '". $_POST['login'] ."'",
				"password = '". $_POST['password'] ."'"
			);
			
			if ($userInfo = Query::SingleRow('users', $where)) {
				// Login succeeded; create cookie and reload this page
				$this->Login($userInfo['id']);
				HTTP::ReloadCurrentURL();
				return true;
			} else {
				print e('p', array('class' => 'error'), $_JAM->strings['admin']['incorrectLogin']);
			}
		}
		// Display form if login was incorrect or user has not yet submitted the form
		$form = new Form();
		$form->Open();
		print $form->Field('login', 40, $_JAM->strings['admin']['login']);
		print $form->Password('password', 40, $_JAM->strings['admin']['password']);
		print $form->Submit('connect', $_JAM->strings['admin']['connect']);
		$form->Close();
	}
	
	function Login($id) {
		if ($this->FetchData($id)) {
			if (Cookie::Create('id', $this->EncodeID($id))) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function Logout() {
		if (Cookie::Delete('id')) {
			HTTP::RedirectLocal();
			return true;
		} else {
			return false;
		}
	}

	function EncodeID($id) {
		// This is, hum, pretty random
		$encodedID = '7' . base64_encode(dechex($id * 1000));
		return $encodedID;
	}
	
	function DecodeID($id) {
		$decodedID = hexdec(base64_decode(ltrim($id, '7'))) / 1000;
		$decodedID = (string) $decodedID;
		return $decodedID;
	}
	
}

?>
