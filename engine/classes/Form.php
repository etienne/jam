<?php

class Form {
	var $action;
	var $method;
	var $values;
	var $string;
	var $missing = array();
	var $invalid = array();
	var $hasFile;
	
	/*
	 * Constructor
	 */
	
	function Form($method = 'post', $action = '') {
		global $_JAM;
		if (!$this->action = $action) {
			// If no action was specified, use the current script
			$this->action = $_SERVER['REQUEST_URI'];
		}
		$this->method = $method;
		return true;
	}
	
	/*
	 * Public
	 */
	
	function Open () {
		ob_start('mb_output_handler');
		return true;
	}
	
	function Close () {
		$formString = ob_get_contents();
		ob_end_clean();
		$params = array(
			'method' => $this->method,
			'action' => $this->action
		);
		if ($this->hasFile) {
			$params['enctype'] = 'multipart/form-data';
		}
		print e('form', $params, $formString);
	}
	
	function LoadValues($values) {
		if ($this->values = $values) {
			return true;
		} else {
			return false;
		}
	}
	
	function LoadValue($field, $value) {
		$this->values[$field] = $value;
	}
	
	function LoadMissingFields($fields) {
		return ($this->missing = $fields) ? true : false;
	}
	
	function LoadInvalidFields($fields) {
		return ($this->invalid = $fields) ? true : false;
	}
	
	function FieldIsMissing($field) {
		return in_array($field, $this->missing);
	}

	function Item($name, $item, $title = '', $class = '', $append = '') {
		// If we supply a title, generate <label/> tag
		if ($title) {
			$itemString = e('label', array('for' => 'form_'. $name), $title);
			$itemString .= e('div', $item) . $append;

			if ($class) $classesArray[] = $class;

			// Look for missing or invalid items
			if (in_array($name, $this->missing)) {
				$classesArray[] = 'missing';
			} elseif (in_array($name, $this->invalid)) {
				$classesArray[] = 'invalid';
			}
			if ($classesArray) {
				$classesString = implode(' ', $classesArray);
				$string = e('div', array('class' => $classesString), $itemString);
			} else {
				$string = e('div', $itemString);
			}
		} else {
			// No title means no label and no div
			$string = $item;
		}
		return $string;
	}

	function Field($name, $width, $title = '', $height = 1, $class = '') {
		if ($height == 1) {
			// Single-line field is an <input/>
			$params = array(
				'id' => 'form_'. $name,
				'name' => $name,
				'type' => 'text',
				'size' => $width,
				'maxlength' => 255,
				'class' => $class
			);
			// Populate field with value
			if (isset($this->values[$name])) {
				$params['value'] = $this->values[$name];
			}
			$string = e('input', $params);
		}
		if ($height > 1) {
			// Multi-line field is a <textarea/>
			$params = array(
				'id' => 'form_'. $name,
				'name' => $name,
				'rows' => $height,
				'cols' => $width,
				'class' => $class
			);
			$string = e('textarea', $params, $this->values[$name]);
		}
		return $this->Item($name, $string, $title);
	}
	
	function Password($name, $width, $title = '') {
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'password',
			'maxlength' => 255,
			'size' => $width
		);
		$string = e('input', $params);
		return $this->Item($name, $string, $title);
	}
	
	function Checkbox ($name, $title = '', $value = 1) {
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'checkbox',
			'value' => $value
		);
		
		if ($this->values[$name]) {
			$params['checked'] = 'checked';
		}

		$string = e('input', $params);
		return $this->Item($name, $string, $title);
	}

	function Radio($name, $value, $title = '') {
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'radio',
			'value' => $value
		);
		
		if ($this->values[$name] == $value) {
			$params['checked'] = 'checked';
		}
		
		$string = e('input', $params);
		return $this->Item($name, $string, $title);
	}
	
	function Select($name, $array, $title = '', $multiple = false, $forbid = '') {
		if (!$forbid) $forbid = array();
		foreach ($array as $key => $value) {
			// We may want to disallow certain values
			if (!in_array($key, $forbid)) {
				$params = array('value' => $key);
				// Pre-select values if provided
				if ($this->values[$name] == $key || @in_array($key, $this->values[$name])) {
					$params['selected'] = 'selected';
				}
				$options .= e('option', $params, $value);
			}
		}
		$attributes = array('id' => 'form_'. $name, 'name' => $name);
		if ($multiple) {
			$attributes['multiple'] = 'multiple';
			$attributes['name'] = $name . '[]';
		}
		$string = e('select', $attributes, $options);
		return $this->Item($name, $string, $title);
	}
	
	function Popup($name, $array, $title = '', $forbid = '') {
		return $this->Select($name, $array, $title, false, $forbid);
	}
	
	function MultipleSelect($name, $array, $title = '', $forbid = '') {
		return $this->Select($name, $array, $title, true, $forbid);
	}

	function Datetime($name, $title, $displayMode = 0) {
		global $_JAM;
		
		/* $displayMode:
		 	0 = date and time
			1 = date only
			2 = time only
		 */
		
		// If we already have a date in $this->values, use that, otherwise use current time
		if ($displayMode == 0 || $displayMode == 1) {
			if ($date = $this->values[$name] ? $this->values[$name] : $_JAM->databaseTime) {
				// Create Date object if we don't already have one
				$class = get_class($item[$field]);
				if (!($class == 'Date' || $class == 'date')) {
					$date = new Date($date);
				}

				// Split it up so we can use it
				$this->values[$name . '_year'] = $date->GetYear();
				$this->values[$name . '_month'] = $date->GetMonth();
				$this->values[$name . '_day'] = $date->GetDay();
				$this->values[$name . '_hour'] = $date->GetHour();
				$this->values[$name . '_minutes'] = $date->GetMinutes();
			}
		} elseif ($displayMode == 2) {
			$time = $this->values[$name] ? $this->values[$name] : '00:00';
			$this->values[$name .'_hour'] = substr($time, 0, 2);
			$this->values[$name .'_minutes'] = substr($time, 3, 2);
		}
		
		// Create arrays for days, hours, and minutes
		for ($i = 1; $i <= 31; $i++) { $daysArray[$i] = $i; }
		for ($i = 0; $i <= 23; $i++) { $hoursArray[$i] = $i; }
		for ($i = 0; $i <= 59; $i++) { $minutesArray[$i] = str_pad($i, 2, '0', STR_PAD_LEFT); }
		
		// Date
		$dateString = $this->Popup($name .'_day', $daysArray);
		$dateString .= $this->Popup($name .'_month', $_JAM->strings['months']);
		$dateString .= $this->Field($name .'_year', 4);
		
		// Time
		$timeString = $this->Popup($name .'_hour', $hoursArray) .':'. $this->Popup($name .'_minutes', $minutesArray);
		
		// Display correct fields according to $displayMode
		switch ($displayMode) {
			case 0:
				$string = $dateString .' '. $timeString;
				break;
			case 1:
				$string = $dateString;
				break;
			case 2:
				$string = $timeString;
				break;
		}
		return $this->Item($name, $string, $title);
	}

	function File($name, $title, $note = '') {
		$this->hasFile = true;
		$phpMaxFileSize = ini_get('upload_max_filesize');
		$maxFileSize = preg_replace(array('/K/','/M/'), array('000','000000'), $phpMaxFileSize);
		$this->Hidden('MAX_FILE_SIZE', $maxFileSize);
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'file'
		);
		$string = ($note ? e('p', $note) : '') . e('input', $params);
		return $this->Item($name, $string, $title);
	}

	function Disabled($name, $note, $title = '') {
		return $this->Item($name, $note, $title);
	}
	
	function Hidden($name, $value = false) {
		if (!$value) {
			$value = $this->values[$name];
		}
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'hidden',
			'value' => $value
		);
		$string = e('input', $params);
		return $this->Item($name, $string);
	}
	
	function Submit($name, $label) {
		$params = array(
			'id' => 'form_'. $name,
			'class' => 'submit',
			'name' => $name,
			'type' => 'submit',
			'value' => $label
		);
		$string = e('input', $params);
		return $this->Item($name, $string);
	}
	
}

?>
