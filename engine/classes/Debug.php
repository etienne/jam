<?php

require_once('classes/Arrays.php');

class Debug {
	
	function Display($thing = '') {
		global $_JAM;
		
		// Make sure user is an admin
		if (!$_JAM->user || !$_JAM->user->IsAdmin()) return false;
		
		// Display passed object
		if (is_array($thing) || is_object($thing)) {
			// Thing is an array or an object; display content or properties
			Arrays::Display($thing);
		} elseif($thing) {
			// Thing is something else; display it directly
			print $thing;
		} else {
			// Display backtrace
			Debug::Backtrace();
		}
	}
	
	function Backtrace() {
		// Display backtrace
		$backtrace = debug_backtrace();
		$list = '';
		print e('p', 'Backtrace:');
		foreach($backtrace as $info) {
			if ($info['class'] != 'debug' && $info['function'] != 'dp') {
				$function = e('strong', $info['class'] . $info['type'] . $info['function']);
				$file = $info['file'] .' on line '. $info['line'];
				$list .= e('li', $function .' in '. $file);
			}
		}
		print e('ul', array('class' => 'backtrace'), $list);
	}
	
}

?>
