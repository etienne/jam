<?php

require_once('classes/Form.php');

class ModuleForm extends Form {
	
	var $module;
	var $errors;
	
	/*
	 * Constructor
	 */	
	
	function ModuleForm(&$module) {
		global $_JAM;
		
		parent::Form();
		
		$this->module = $module;
		
		// Load existing values into form, if available
		if ($this->module->postData) {
			// Strip slashes before displaying data
			foreach ($this->module->postData as $key => $data) {
				$cleanArray[$key] = stripslashes($data);
			}
			$this->LoadValues($cleanArray);
		} else {
			$itemID = $this->module->item['id'] ? $this->module->item['id'] : $this->module->itemID;
			if ($this->module->rawData) {
				$this->LoadValues($this->module->rawData[$itemID]);
			} elseif ($this->module->item) {
				$this->LoadValues($this->module->item);
			}
		}
		
		// Load missing fields into form and display error, if applicable
		if ($this->module->missingData) {
			$this->LoadMissingFields($this->module->missingData);
			$errorString =
				$this->module->strings['fields']['missingData'] ?
				$this->module->strings['fields']['missingData'] :
				$_JAM->strings['admin']['missingData'];
			$params = array('class' => 'errorMissing');
			$this->errors .= e('p', $params, $errorString);
		}
		
		// Load invalid fields into form and display error, if applicable
		if ($this->module->invalidData) {
			$this->LoadInvalidFields($this->module->invalidData);
			$errorString =
				$this->module->strings['fields']['invalidData'] ?
				$this->module->strings['fields']['invalidData'] :
				$_JAM->strings['admin']['invalidData'];
			$params = array('class' => 'errorInvalid');
			$this->errors .= e('p', $params, $errorString);
		}
		
		// Display error if a file upload failed
		if ($this->module->fileUploadError) {
			/* FIXME: File upload error display is broken
			switch ($errorCode) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$errorString = $_JAM->strings['admin']['fileUploadErrorSize'];
					break;
				case UPLOAD_ERR_PARTIAL:
					$errorString = $_JAM->strings['admin']['fileUploadErrorPartial'];
					break;
				default:
					$errorString = $_JAM->strings['admin']['fileUploadErrorUnknown'];
					break;
			}
			*/
			if (!$errorString = $this->module->strings['fields']['fileUploadError']) {
				$errorString = $_JAM->strings['admin']['fileUploadError'];
			}
			$params = array('class' => 'errorFileUpload');
			$this->errors .= e('p', $params, $errorString);
		}
		
	}
	
	/*
	 * Public
	 */
	
	function Open () {
		ob_start('mb_output_handler');
		print $this->errors;
		return true;
	}
	
	function Item($name, $item, $title = '') {
		// Some items should have a specific class
		if ($showIfArray = $this->module->schema[$name]['showIf']) {
			$field = $showIfArray[0];
			$value = $showIfArray[1];
			// Check whether it is a valid field
			if ($this->module->schema[$field]) {
				$class = 'hidden '. $field . $value;
			}
		}

		// Include note if provided and only if we're already displaying a title
		if ($title && $this->module->strings['notes'][$name]) {
			$note = e('div', array('class' => 'note'), $this->module->strings['notes'][$name]);
		}
		
		return parent::Item($name, $item, $title, $class, $note);
	}
	
	function AutoItem($name, $title = '') {
		global $_JAM;
		
		$info = $this->module->schema[$name];
		
		// Look for default value if this item has no value
		if (!isset($this->values[$name]) && isset($info['default'])) {
			$this->LoadValue($name, $info['default']);
		}
		
		// Check whether we have sufficient privilege to edit
		if ($info['canEdit'] && !$_JAM->user->HasPrivilege($info['canEdit'])) {
			// Determine what to display
			if ($this->values[$name]) {
				$note = $this->values[$name];
			} else {
				$note = $_JAM->strings['admin']['na'];
			}
			$note = e('span', array('class' => 'disabled'), $note);
			return $this->Disabled($name, $note, $title);
		}
		
		// Use hidden field when 'hidden' value is true
		if ($info['hidden']) {
			return $this->Hidden($name);
		}
		
		switch ($info['type']) {
			case 'string':
				return $this->Field($name, 40, $title);
				break;
			case 'password':
				if ($_JAM->user->IsAdmin()) {
					// Show as regular field for user with admin privileges
					return $this->Field($name, 40, $title);
				} else {
					return $this->Password($name, 40, $title);
				}
				break;
			case 'shorttext':
				return $this->Field($name, 30, $title, 5);
				break;
			case 'text':
				if ($info['wysiwyg']) {
					return $this->Field($name, 30, $title, 22, 'wysiwyg');
				} else {
					return $this->Field($name, 30, $title, 22);
				}
				break;
			case 'int':
			case 'signedint':
			case 'multi':
				// Display appropriate related data if available, else display a plain field
				if ($info['relatedModule'] || $info['relatedArray']) {
					if ($relatedData = $this->module->GetRelatedArray($name)) {
						if ($info['type'] == 'multi') {
							return $this->MultipleSelect($name, $relatedData, $title);
						} else {
							// Add "none" option for non-required fields
							if ($info['relatedModule'] && !$info['required']) {
								$noneArray = array(0 => $_JAM->strings['admin']['noOption']);
								$relatedData = $noneArray + $relatedData;
							}
							return $this->Popup($name, $relatedData, $title);
						}
					} else {
						$note = e('span', array('class' => 'disabled'), $_JAM->strings['admin']['na']);
						return $this->Disabled($name, $note, $title);
					}
				} else {
					return $this->Field($name, 5, $title);
				}
				break;
			case 'timestamp':
			case 'datetime':
				return $this->Datetime($name, $title);
				break;
			case 'date':
				return $this->Datetime($name, $title, 1);
				break;
			case 'time':
				return $this->Datetime($name, $title, 2);
				break;
			case 'bool':
				return $this->Checkbox($name, $title);
				break;
			case 'file':
				$hidden = '';
				if ($this->values[$name]) {
					$hidden = $this->Hidden($name .'_id', $this->module->item[$name]->itemID);
					// A file has already been uploaded
					$inputParams = array(
						'id' => 'deleteFile_'. $name,
						'name' => 'deleteFile_'. $name,
						'type' => 'checkbox',
						'value' => 1
					);
					$checkbox = e('span', array('class' => 'fileDeleteCheckbox'), e('input', $inputParams) . $_JAM->strings['admin']['deleteThisFile']);
					$filePath = $this->module->item[$name]->item['path'];
					switch ($this->module->item[$name]->item['type']) {
						case 'image/png':
						case 'image/jpeg':
						case 'image/gif':
							$image = i($filePath .'?context=adminThumbnail', $_JAM->strings['admin']['thumbnail']);
							$fileLink = a($filePath, $image, array('class' => 'thumbnail'));
							break;
						default:
							$fileIcon = i('assets/images/admin_file.png', $_JAM->strings['admin']['fileIcon']);
							$filePath = a($this->module->item[$name]->item['path'], $this->module->item[$name]->item['filename']);
							$fileLink = $fileIcon . $filePath;
							break;
					}
					$note = $checkbox . $fileLink;
				} else {
					// No file has been uploaded yet
					$note = $_JAM->strings['admin']['noFile'];
				}
				return $hidden . $this->File($name, $title, $note);
				break;
		}
	}
	
	function Submit($label = '') {
		global $_JAM;
		$hidden = $this->Hidden('module', $this->module->name);
		
		$id = $this->module->item['master'] ? $this->module->item['master'] : $this->module->itemID;
		if ($id) {
			$hidden .= $this->Hidden('master', $id);
			$action = 'edit';
		} else {
			$action = 'add';
		}
		
		// Determine submit button string
		$customString = $label ? $label : $this->module->strings['fields'][$this->module->parentModule->name .'.'. $action];
		if (!$submitString = $customString) {
			$submitString = $_JAM->strings['admin'][$action];
		}
		
		return $hidden . parent::Submit('update', $submitString);
	}
	
}

?>