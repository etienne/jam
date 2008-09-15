<?php

class ModuleForm extends Form {
	
	var $module;
	var $errors;
	
	/*
	 * Constructor
	 */	
	
	function ModuleForm(&$module) {
		global $_JAG;
		
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
				$_JAG['strings']['admin']['missingData'];
			$params = array('class' => 'errorMissing');
			$this->errors .= e('p', $params, $errorString);
		}
		
		// Load invalid fields into form and display error, if applicable
		if ($this->module->invalidData) {
			$this->LoadInvalidFields($this->module->invalidData);
			$errorString =
				$this->module->strings['fields']['invalidData'] ?
				$this->module->strings['fields']['invalidData'] :
				$_JAG['strings']['admin']['invalidData'];
			$params = array('class' => 'errorInvalid');
			$this->errors .= e('p', $params, $errorString);
		}
		
		// Display error if a file upload failed
		if ($this->module->fileUploadError) {
			/* FIXME: File upload error display is broken
			switch ($errorCode) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorSize'];
					break;
				case UPLOAD_ERR_PARTIAL:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorPartial'];
					break;
				default:
					$errorString = $_JAG['strings']['admin']['fileUploadErrorUnknown'];
					break;
			}
			*/
			if (!$errorString = $this->module->strings['fields']['fileUploadError']) {
				$errorString = $_JAG['strings']['admin']['fileUploadError'];
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
	
	function AutoItem($name, $title = '') {
		global $_JAG;
		
		$info = $this->module->schema[$name];
		
		// Look for default value if this item has no value
		if (!isset($this->values[$name]) && isset($info['default'])) {
			$this->LoadValue($name, $info['default']);
		}
		
		// Use hidden field when 'hidden' value is true
		if ($info['hidden']) {
			return $form->Hidden($name);
		}
		
		switch ($info['type']) {
			case 'string':
				return $this->Field($name, 40, $title);
				break;
			case 'password':
				if ($_JAG['user']->IsAdmin()) {
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
				return $this->Field($name, 30, $title, 22);
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
								$noneArray = array(0 => $_JAG['strings']['admin']['noOption']);
								$relatedData = $noneArray + $relatedData;
							}
							return $this->Popup($name, $relatedData, $title);
						}
					} else {
						$note = e('span', array('class' => 'disabled'), $_JAG['strings']['admin']['na']);
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
				return $this->Datetime($name, $title, false);
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
					$checkbox = e('span', array('class' => 'fileDeleteCheckbox'), e('input', $inputParams) . $_JAG['strings']['admin']['deleteThisFile']);
					$filePath = $this->module->item[$name]->item['path'];
					switch ($this->module->item[$name]->item['type']) {
						case 'image/png':
						case 'image/jpeg':
						case 'image/gif':
							$image = i($filePath .'?context=adminThumbnail', $_JAG['strings']['admin']['thumbnail']);
							$fileLink = a($filePath, $image, array('class' => 'thumbnail'));
							break;
						default:
							$fileIcon = i('assets/images/admin_file.png', $_JAG['strings']['admin']['fileIcon']);
							$filePath = a($this->module->item[$name]->item['path'], $this->module->item[$name]->item['filename']);
							$fileLink = $fileIcon . $filePath;
							break;
					}
					$note = $checkbox . $fileLink;
				} else {
					// No file has been uploaded yet
					$note = $_JAG['strings']['admin']['noFile'];
				}
				return $hidden . $this->File($name, $title, $note);
				break;
		}
	}
	
	function Submit($label = '') {
		global $_JAG;
		$hidden = $this->Hidden('module', $this->module->name);
		
		$id = $this->module->item['master'] ? $this->module->item['master'] : $this->module->itemID;
		if ($id) {
			$hidden .= $this->Hidden('master', $id);
			$action = 'edit';
		} else {
			$action = 'new';
		}
		
		// Determine submit button string
		$customString = $label ? $label : $this->module->strings['fields'][$this->module->parentModule->name .'.'. $action];
		if (!$submitString = $customString) {
			$submitString = $_JAG['strings']['admin'][$action];
		}
		
		return $hidden . parent::Submit('update', $submitString);
	}
	
}

?>