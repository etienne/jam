<?php

class NewsModule extends Module {

	function DefaultViewController() {
		$params = array(
			'fields' => array('title', 'body', 'category', 'modified'),
			'orderby' => 'modified DESC'
		);
		$this->FetchItems($params);
	}

	function HomeViewController() {
		$params = array(
			'fields' => array('title', 'teaser', 'category', 'modified'),
			'orderby' => 'modified DESC'
		);
		$this->FetchItems($params);
	}

}

?>