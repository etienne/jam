<?php

class FilesModule extends Module {
	
	var $originalFilenames;
	
	function FetchItems($queryParams = '') {
		global $_JAM;
		
		parent::FetchItems($queryParams);
		
		// Get file size
		foreach ($this->items as $id => $item) {
			$filePath = $_JAM->filesDirectory . $id;
			if (file_exists($filePath)) {
				if (!$item['filesize'] && $filesize = filesize($filePath)) {
					$this->items[$id]['filesize'] = $filesize;
				}
			}
		}
		
		return $this->items;
	}
	
	function DeleteItem($item) {
		global $_JAM;
		
		parent::DeleteItem($item);
		
		if (!unlink($_JAM->filesDirectory . $item)) {
			trigger_error("Couldn't delete old file", E_USER_ERROR);
		}
	}
	
	function AddUploadedFile($field) {
		global $_JAM;
		
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
		$destinationFile = $_JAM->filesDirectory . $fileID;
		
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
		global $_JAM;
		$originalFilename = $this->item['filename'] ? $this->item['filename'] : $this->originalFilenames[$field];
		if (method_exists($this->parentModule, 'GetFilePath')) {
			return $this->parentModule->GetFilePath($field);
		} elseif ($itemPath = $this->parentModule->item['path']) {
			return $itemPath .'/'. $originalFilename;
		} elseif ($itemPath = $this->parentModule->config['path'][$_JAM->language]) {
			return $itemPath .'/'. $originalFilename;
		} else {
			return $originalFilename;
		}
	}

	
	function ItemViewController() {
		global $_JAM;
		
		// Get list of contexts
		$contexts = IniFile::Parse('engine/config/imageContexts.ini', true);
		if ($appContexts = IniFile::Parse('app/config/imageContexts.ini', true)) {
			$contexts += $appContexts;
		}

		// Get path to file
		$file = $_JAM->filesDirectory . $this->itemID;

		if ($context = $contexts[$_GET['context']]) {
			$image = new Image($file);

			// Set dimensions according to context
			$width = $context['width'];
			$height = $context['height'];

			if ($context['allowScaleUp'] === '') {
				// Scale up is forbidden; check whether we're scaling up
				if ($context['width'] > $image->width || $context['height'] > $image->height) {
					// We're indeed scaling up; override context dimensions with actual file dimensions
					$width = $image->width;
					$height = $image->height;
				}
			}

			$image->OutputResizedImage($width, $height);
		} else {
			// Set MIME type, if available
			if ($this->items[$this->itemID]['type']) {
				header('Content-type: '. $this->items[$this->itemID]['type']);
			}

			// Determine file size
			if ($fileSize = filesize($file)) {
				header('Content-length: '. $fileSize);
			}

			// Read file directly from 'files' directory
			readfile($file);
		}

		// Don't display anything else; this also bypasses caching, which is good
		exit;
	}
	
}

?>
