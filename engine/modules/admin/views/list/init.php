<?php

// Check whether there's a key query
if (!$queryParams = Module::ParseConfigFile($this->name, 'config/keyQuery.ini', true)) {
	// Look for key fields if no key queries were found
	foreach ($this->schema as $name => $info) {
		if ($info['key']) {
			$queryParams = array('fields' => $name);
		}
	}
}

// Fetch data
$this->FetchItems($queryParams);

if ($this->items) {
	// Determine which field is what
	foreach (reset($this->items) as $field => $data) {
		if ($this->schema[$field]['type'] == 'id' || $field == 'master') {
			$this->template['idColumn'] = $field;
		} elseif ($field != 'language' && $field != 'path') { // FIXME: Kludgy.
			$this->template['keyColumn'] = $field;
			break;
		}
	}
}

?>