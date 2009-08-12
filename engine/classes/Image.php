<?php

class Image {
	
	var $file;
	var $width;
	var $height;
	var $type;
	var $sourceObject;
	
	function Image($file) {
		$this->file = $file;
		
		// Get size of source image
		$imageInfo = getimagesize($this->file);
		$this->width = $imageInfo[0];
		$this->height = $imageInfo[1];
		
		// Get MIME type
		$this->type = $imageInfo['mime'];
		
		// Create image object
		switch ($this->type) {
			case 'image/jpeg':
				$this->sourceObject = imagecreatefromjpeg($this->file);
				break;
			case 'image/png':
				$this->sourceObject = imagecreatefrompng($this->file);
				break;
			case 'image/gif':
				$this->sourceObject = imagecreatefromgif($this->file);
				break;
		}
	}
	
	function OutputResizedImage($width, $height) {
		global $_JAM;
		
		// Determine output size
		$autoWidth = round($this->width * ($height / $this->height));
		$autoHeight = round($this->height * ($width / $this->width));

		if ($width && $height) {
			// Fixed width and height
			$canvasWidth = $width;
			$canvasHeight = $height;
			if ($this->width > $this->height) {
				// Wide image
				$imageWidth = $autoWidth;
				$imageHeight = $height;
			} else {
				// Tall image
				$imageWidth = $width;
				$imageHeight = $autoHeight;
			}
		} elseif ($width && !$height) {
			// Fixed width, auto height
			$canvasWidth = $imageWidth = $width;
			$canvasHeight = $imageHeight = $autoHeight;
		} elseif (!$width && $height) {
			// Fixed height, auto width
			$canvasWidth = $imageWidth = $autoWidth;
			$canvasHeight = $imageHeight = $height;
		} else {
			// No dimensions were provided
			return false;
		}

		// Create image object
		$outputObject = imagecreatetruecolor($canvasWidth, $canvasHeight);
		
		// Resize image and copy/resize into image object
		$xCoordinate = round(-($imageWidth - $canvasWidth) / 2);
		$yCoordinate = round(-($imageHeight - $canvasHeight) / 2);
		imagecopyresampled(
			$outputObject,
			$this->sourceObject,
			$xCoordinate,
			$yCoordinate,
			0,
			0,
			$imageWidth,
			$imageHeight,
			$this->width,
			$this->height
		);
		
		// Set MIME type to JPEG
		header('Content-type: image/jpeg');
		
		// We want a progressive JPEG
		imageinterlace($outputObject, true);

		// Output image
		imagejpeg($outputObject, null, 90);
		return true;
	}
	
}

?>