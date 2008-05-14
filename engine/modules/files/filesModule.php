<?php

class FilesModule extends Module {

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
		$originalFilename = $_FILES[$field]['name'];
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
			'filename' => $originalFilename,
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
		
		// Determine path (using custom method, if available)
		if (method_exists($this->parentModule, 'GetFilePath')) {
			$path = $this->parentModule->GetFilePath($field, $item);
		} elseif ($itemPath = $this->parentModule->path) {
			$path = $itemPath .'/'. $originalFilename;
		} else {
			$path = $originalFilename;
		}
		if (!Path::Insert($path, $this->moduleID, $fileID)) {
			trigger_error("Couldn't insert path for uploaded file", E_USER_ERROR);
		}
		
		// Delete previous item if applicable
		$previousFileID = $this->parentModule->postData[$field];
		if (!$this->parentModule->config['keepVersions'] && $previousFileID) {
			$this->DeleteItem($previousFileID);
		}
		
		return $fileID;
	}

}

?>
