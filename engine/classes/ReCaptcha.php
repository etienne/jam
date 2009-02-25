<?php

require_once('libraries/recaptchalib.php');

class ReCaptcha {
	
	/* Static */
	
	function Display($publicKey, $theme = 'clean') {
		global $_JAG;
		$settingsString = "var RecaptchaOptions = { theme : '". $theme ."', lang : '". $_JAG['language'] ."' };";
		$scriptElement = e('script', $settingsString);
		print $scriptElement;
		print recaptcha_get_html($publicKey);
	}
	
	function Check($privateKey) {
		$response = recaptcha_check_answer(
			$privateKey,
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);
		return $response->is_valid;
	}
	
}

?>