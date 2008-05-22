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
		global $_JAG;
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

	function AddItem($name, $item, $title = '') {
		// If we supply a title, generate <label/> tag
		if ($title) {
			$label = e('label', array('for' => 'form_'. $name), $title);
			$itemDiv = e('div', $item);
			// Look for missing or invalid items
			if (in_array($name, $this->missing)) {
				$string = e('div', array('class' => 'missing'), $label . $itemDiv);
			} elseif (in_array($name, $this->invalid)) {
				$string = e('div', array('class' => 'invalid'), $label . $itemDiv);
			} else {
				$string = e('div', $label . $itemDiv);
			}
		} else {
			// No title means no label and no div
			$string = $item;
		}
		$this->string .= $string;
		return true;
	}

	function AddField($name, $width, $title = '', $height = 1) {
		if ($height == 1) {
			// Single-line field is an <input/>
			$params = array(
				'id' => 'form_'. $name,
				'name' => $name,
				'type' => 'text',
				'size' => $width,
				'maxlength' => 255
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
				'cols' => $width
			);
			$string = e('textarea', $params, $this->values[$name]);
		}
		$this->AddItem($name, $string, $title);
		return true;
	}
	
	function AddPassword($name, $width, $title = '') {
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'password',
			'maxlength' => 255,
			'size' => $width
		);
		$string = e('input', $params);
		$this->AddItem($name, $string, $title);
		return true;
	}
	
	function AddCheckbox ($name, $title) {
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'checkbox',
			'value' => 1,
		);
		
		if ($this->values[$name]) {
			$params['checked'] = 'checked';
		}

		$string = e('input', $params);
		$this->AddItem($name, $string, $title);
		return true;
	}

	function AddSelect($name, $array, $title = '', $multiple = false, $forbid = '') {
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
		$this->AddItem($name, $string, $title);
		return true;
	}
	
	function AddPopup($name, $array, $title = '', $forbid = '') {
		$this->AddSelect($name, $array, $title, false, $forbid);
	}
	
	function AddMultipleSelect($name, $array, $title = '', $forbid = '') {
		$this->AddSelect($name, $array, $title, true, $forbid);
	}

	function AddDatetime($name, $title) {
		global $_JAG;

		// If we already have a date in $this->values, use that, otherwise use current time
		if ($dateString = $this->values[$name] ? $this->values[$name] : $_JAG['databaseTime']) {
			// Split it up so we can use it
			$date = new Date($dateString);
			$this->values[$name . '_year'] = $date->GetYear();
			$this->values[$name . '_month'] = $date->GetMonth();
			$this->values[$name . '_day'] = $date->GetDay();
			$this->values[$name . '_hour'] = $date->GetHour();
			$this->values[$name . '_minutes'] = $date->GetMinutes();
		}
		
		// Create form elements group
		$formGroup = $this->GetNewFormGroup();
		
		// Create arrays for days, hours, and minutes
		for ($i = 1; $i <= 31; $i++) { $daysArray[$i] = $i; }
		for ($i = 0; $i <= 23; $i++) { $hoursArray[$i] = $i; }
		for ($i = 0; $i <= 59; $i++) { $minutesArray[$i] = str_pad($i, 2, '0', STR_PAD_LEFT); }
		
		// Date
		$formGroup->AddPopup($name .'_day', $daysArray);
		$formGroup->AddPopup($name .'_month', $_JAG['strings']['months']);
		$formGroup->AddField($name .'_year', 4);
		
		// Space
		$formGroup->AddArbitraryData('  ');
		
		// Time
		$formGroup->AddPopup($name .'_hour', $hoursArray);
		$formGroup->AddArbitraryData(':');
		$formGroup->AddPopup($name .'_minutes', $minutesArray);
		
		$string = $formGroup->GetString();
		
		$this->AddItem($name, $string, $title);
		return true;
	}

	function AddFile($name, $title, $note = '') {
		$this->hasFile = true;
		$phpMaxFileSize = ini_get('upload_max_filesize');
		$maxFileSize = preg_replace(array('/K/','/M/'), array('000','000000'), $phpMaxFileSize);
		$this->AddHidden('MAX_FILE_SIZE', $maxFileSize);
		$params = array(
			'id' => 'form_'. $name,
			'name' => $name,
			'type' => 'file'
		);
		$string = ($note ? e('p', $note) : '') . e('input', $params);
		$this->AddItem($name, $string, $title);
		return true;
	}

	function AddDisabled($name, $note, $title = '') {
		$this->AddItem($name, $note, $title);
	}
	
	function AddHidden($name, $value = false) {
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
		$this->AddItem($name, $string);
		return true;
	}
	
	function AddSubmit($name, $label) {
		$params = array(
			'id' => 'form_'. $name,
			'class' => 'submit',
			'name' => $name,
			'type' => 'submit',
			'value' => $label
		);
		$string = e('input', $params);
		$this->AddItem($name, $string);
		return true;
	}
	
	function AddArbitraryData($string) {
		$this->string .= $string;
		return true;
	}
	
	function GetNewFormGroup() {
		return new FormGroup($this);
	}
	
	function GetString() {
		$params = array(
			'method' => $this->method,
			'action' => $this->action
		);
		if ($this->hasFile) {
			$params['enctype'] = 'multipart/form-data';
		}
		$string = e('form', $params, $this->string);
		return $string;
	}
	
	function Display() {
		if (print $this->GetString()) {
			return true;
		} else {
			return false;
		}
	}
}

?>
