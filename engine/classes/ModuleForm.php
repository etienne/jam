<?php


class ModuleForm extends Form {
	
	var $module;
	
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
		} elseif ($this->module->rawData[$this->module->itemID]) {
			$this->LoadValues($this->module->rawData[$this->module->itemID]);
		}
		
		// Load missing fields into form and display error, if applicable
		if ($this->module->missingData) {
			$this->LoadMissingFields($this->module->missingData);
			$errorString =
				$this->module->strings['form']['missingData'] ?
				$this->module->strings['form']['missingData'] :
				$_JAG['strings']['admin']['missingData'];
			$params = array('class' => 'errorMissing');
			$this->AddArbitraryData(e('p', $params, $errorString));
		}
		
		// Load invalid fields into form and display error, if applicable
		if ($this->module->invalidData) {
			$this->LoadInvalidFields($this->module->invalidData);
			$errorString =
				$this->module->strings['form']['invalidData'] ?
				$this->module->strings['form']['invalidData'] :
				$_JAG['strings']['admin']['invalidData'];
			$params = array('class' => 'errorInvalid');
			$this->AddArbitraryData(e('p', $params, $errorString));
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
			if (!$errorString = $this->module->strings['form']['fileUploadError']) {
				$errorString = $_JAG['strings']['admin']['fileUploadError'];
			}
			$params = array('class' => 'errorFileUpload');
			$this->AddArbitraryData(e('p', $params, $errorString));
		}
		
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
			$form->AddHidden($name);
			continue;
		}

		switch ($info['type']) {
			case 'string':
				$this->AddField($name, 40, $title);
				break;
			case 'password':
				if ($_JAG['user']->IsAdmin()) {
					// Show as regular field for user with admin privileges
					$this->AddField($name, 40, $title);
				} else {
					$this->AddPassword($name, 40, $title);
				}
				break;
			case 'lang':
				$languagesArray = $_JAG['strings']['languages'];
				$this->AddPopup($name, $languagesArray, $title);
				break;
			case 'shorttext':
				$this->AddField($name, 30, $title, 5);
				break;
			case 'text':
				$this->AddField($name, 30, $title, 22);
				break;
			case 'int':
			case 'signedint':
			case 'multi':
				// Display appropriate related data if available, else display a plain field
				if ($info['relatedModule'] || $info['relatedArray']) {
					if ($relatedData = $this->module->GetRelatedArray($name)) {
						if ($info['type'] == 'multi') {
							$this->AddMultipleSelect($name, $relatedData, $title);
						} else {
							// Add "none" option for non-required fields
							if (!$info['required']) {
								$noneArray = array(0 => $_JAG['strings']['admin']['noOption']);
								$relatedData = $noneArray + $relatedData;
							}
							$this->AddPopup($name, $relatedData, $title);
						}
					} else {
						$note = e('span', array('class' => 'disabled'), $_JAG['strings']['admin']['na']);
						$this->AddDisabled($name, $note, $title);
					}
				} else {
					$this->AddField($name, 5, $title);
				}
				break;
			case 'timestamp':
			case 'datetime':
				$this->AddDatetime($name, $title);
				break;
			case 'bool':
				$this->AddCheckbox($name, $title);
				break;
			case 'file':
				if ($this->values[$name]) {
					$this->AddHidden($name .'_id', $this->module->item[$name]->itemID);
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
				$this->AddFile($name, $title, $note);
				break;
		}
		
	}
	
	function AddSubmit() {
		global $_JAG;
		$this->AddHidden('module', $this->module->name);
		
		$id = $this->module->item['master'] ? $this->module->item['master'] : $this->module->itemID;
		if ($id) {
			$this->AddHidden('master', $id);
			$action = 'edit';
		} else {
			$action = 'new';
		}
		
		// Determine submit button string
		$customString = $this->module->strings['form'][$this->module->parentModule->name .'.'. $action];
		if ($customString) {
			$submitString = $customString;
		} else {
			$submitString = $_JAG['strings']['admin'][$action];
		}
		
		parent::AddSubmit('update', $submitString);
	}
	
}

?>