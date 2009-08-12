<?php

require_once('classes/String.php');
require_once('classes/TextRenderer.php');

class HTML {
	
	function Element ($elementName, $argument1 = false, $argument2 = false) {
		global $_JAM;
		
		if (is_array($argument1)) {
			// The first argument is an array and represents the element's attributes
			// Convert attributes to string
			foreach ($argument1 as $attributeName => $attributeValue) {
				$attributesString .= ' '. $attributeName .'="'. $attributeValue .'"';
			}
			// The second argument represents the element's content
			$content = $argument2;
		} elseif (is_string($argument1)) {
			// The first argument represents the element's content; the second is not used
			$content = $argument1;
		}

		// Some elements need a close tag even though they're empty
		$elementsWithCloseTags = array('textarea');
		if ($content || in_array($elementName, $elementsWithCloseTags)) {
			$closeTag = HTML::ElementClose($elementName);
		}
		
		// Some elements can be empty and self-closed
		$selfClosingElements = array('base', 'link', 'meta', 'hr', 'br', 'img', 'embed', 'param', 'area', 'col', 'input');
		$selfClose = (!$_JAM->projectConfig['useHTML4'] && !$content && in_array($elementName, $selfClosingElements));

		return '<'. $elementName . $attributesString . ($selfClose ? ' />' : '>') . $content . $closeTag;
	}
	
	function ElementClose ($elementName) {
		return '</'. $elementName .'>';
	}
	
	function Anchor ($url, $text, $attributes = false) {
		if (!String::IsURL($url) && strpos($url, '#') !== 0) {
			$url = ROOT . $url;
		}
		$hrefAttribute = array('href' => $url);
		if (is_array($attributes)) {
			$mergedAttributes = $attributes + $hrefAttribute;
		} else {
			$mergedAttributes = $hrefAttribute;
		}
		return HTML::Element('a', $mergedAttributes, $text);
	}
	
	function Image ($path, $alt, $attributes = false) {
		global $_JAM;
		
		// Check whether $path is an absolute URL
		if (String::IsURL($path)) {
			// This is an absolute URL
			$absolutePath = $path;
		} else {
			$path = ROOT . $path;
		}
		
		$attributes['src'] = $path;
		$attributes['alt'] = $alt;

		// TODO: Eventually, maybe add width/height HTML attributes for local images
		
		// Try to get width/height for image
		if ($absolutePath && $imageInfo = @getimagesize($absolutePath)) {
			$attributes['width'] = $imageInfo[0];
			$attributes['height'] = $imageInfo[1];
		}
		
		return HTML::Element('img', $attributes);
	}
	
	function SWFObject($swf, $container, $width, $height, $version) {
		$string = '
			<script type="text/javascript">
				swfobject.embedSWF("'. $swf .'", "'. $container .'", "'. $width .'", "'. $height .'", "'. $version .'");
			</script>';
		return $string;
	}
	
	function EncodedEmail($email, $text, $title = null) {
		require_once('engine/libraries/enkoder.php');
		return Enkode::enkode_mail($email, $text, $title);
	}
	
}

?>
