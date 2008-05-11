<?php

// HTML element
function e ($a, $b = false, $c = false) {
	return HTML::Element($a, $b, $c);
}

// Anchor
function a ($url, $text, $attributes = false) {
	return HTML::Anchor($url, $text, $attributes);
}

// Image
function i ($url, $alt, $attributes = false) {
	return HTML::Image($url, $alt, $attributes);
}

// Debug print
function dp ($string = '') {
	Debug::Display($string);
}

?>
