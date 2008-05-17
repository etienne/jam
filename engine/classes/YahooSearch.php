<?php

class YahooSearch {
	
	var $dom;
	var $totalResultsAvailable;
	var $totalResultsReturned;
	var $firstResultPosition;
	
	/* Constructor */

	function YahooSearch($applicationID, $terms, $scope = '') {
		/* Most of this taken from Yahoo sample code. */
		
		// Formulate query
		$query = '?query='. rawurlencode($terms);
		$query .= '&appid='. $applicationID;
		if ($scope) {
			$query .= '&site='. $scope;
		}
		
		// Get result from API
		$resultXML = file_get_contents('http://search.yahooapis.com/WebSearchService/V1/webSearch'. $query);
		
		// Parse the XML and check it for errors
		if (!$this->dom = domxml_open_mem($resultXML, DOMXML_LOAD_PARSING, $error)) {
			trigger_error("Couldn't parse XML", E_USER_WARNING);
		} else {
			$root = $this->dom->document_element();
			$this->totalResultsAvailable = $root->get_attribute('totalResultsAvailable');
			$this->totalResultsReturned = $root->get_attribute('totalResultsReturned');
			$this->firstResultPosition = $root->get_attribute('firstResultPosition');
		}
	}
	
	/* Public */

	function GetResultsArray() {
		if (!$this->dom) {
			return false;
		}
		
		$root = $this->dom->document_element();

		$node = $root->first_child();
		$i = 0;
		while($node) {
			switch($node->tagname) {
				case 'Result':
				$subnode = $node->first_child();
				while($subnode) {
					$subnodes = $subnode->child_nodes();
					if(!empty($subnodes)) foreach($subnodes as $k=>$n) {
						if(empty($n->tagname)) {
							$res[$i][$subnode->tagname] = trim($n->get_content());
						} else {
							$res[$i][$subnode->tagname][$n->tagname]=trim($n->get_content());
						}
					}
					$subnode = $subnode->next_sibling();
				}
				break;
				default:
				$i--;
				break;
			}
			$i++;
			$node = $node->next_sibling();
		}  
		return $res;
	}
	
	
}


?>
