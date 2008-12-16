<?php

class FilesModule extends Module {
	
	var $originalFilenames;
	
	function FetchItems($queryParams = '') {
		global $_JAG;
		
		parent::FetchItems($queryParams);
		
		// Get file size
		foreach ($this->items as $id => $item) {
			$filePath = $_JAG['filesDirectory'] . $id;
			if (file_exists($filePath)) {
				if (!$item['filesize'] && $filesize = filesize($filePath)) {
					$this->items[$id]['filesize'] = $filesize;
				}
			}
		}
		
		return $this->items;
	}
	
	function DeleteItem($item) {
		global $_JAG;
		
		parent::DeleteItem($item);
		
		if (!unlink($_JAG['filesDirectory'] . $item)) {
			trigger_error("Couldn't delete old file", E_USER_ERROR);
		}
	}
	
	function AddUploadedFile($field) {
		global $_JAG;
		
		$tempFilename = $_FILES[$field]['tmp_name'];
		$this->originalFilenames[$field] = $_FILES[$field]['name'];
		$fileType = $_FILES[$field]['type'];
		
		// If we lack a filetype, try to use GetID3 to figure it out
		if (!$filetype) {
			$getID3 = new getID3();
			if ($fileInfo = $getID3->analyze($tempFilename)) {
				$fileType = $fileInfo['mime_type'] ? $fileInfo['mime_type'] : '';
			}
		}
		
		// Make sure this is a legitimate PHP file upload
		if (!is_uploaded_file($tempFilename)) {
			trigger_error("There is no legitimate uploaded file", E_USER_ERROR);
			return false;
		}
		
		// Insert into files table
		$params = array(
			'filename' => $this->originalFilenames[$field],
			'type' => $fileType
		);
		if (!Database::Insert('files', $params)) {
			trigger_error("Couldn't insert file into database", E_USER_ERROR);
		}
		
		// Get just-inserted ID of file in files table
		$fileID = Database::GetLastInsertID();
		
		// Files are named with their ID
		$destinationFile = $_JAG['filesDirectory'] . $fileID;
		
		// Move file to destination directory
		if (!move_uploaded_file($tempFilename, $destinationFile)) {
			// Move failed
			if (!Database::DeleteFrom('files', 'id = '. $fileID)) {
				trigger_error("Couldn't delete database entry for nonexistent file", E_USER_ERROR);
			}
			trigger_error("Couldn't move temporary file to files directory", E_USER_ERROR);
			return false;
		}
		
		// Delete previous item if applicable
		$previousFileID = $this->parentModule->postData[$field];
		if (!$this->parentModule->config['keepVersions'] && $previousFileID) {
			$this->DeleteItem($previousFileID);
		}
		
		return $fileID;
	}
	
	function GetPath($field) {
		global $_JAG;
		$originalFilename = $this->item['filename'] ? $this->item['filename'] : $this->originalFilenames[$field];
		if (method_exists($this->parentModule, 'GetFilePath')) {
			return $this->parentModule->GetFilePath($field);
		} elseif ($itemPath = $this->parentModule->item['path']) {
			return $itemPath .'/'. $originalFilename;
		} elseif ($itemPath = $this->parentModule->config['path'][$_JAG['language']]) {
			return $itemPath .'/'. $originalFilename;
		} else {
			return $originalFilename;
		}
	}

}

?>
