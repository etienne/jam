<?php

class Database {

	function Connect ($host, $username, $password, $db) {
		if ($link = mysql_connect($host, $username, $password)) {
			if (mysql_select_db($db, $link)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function GetErrorNumber () {
		return mysql_errno();
	}
	
	function GetError () {
		return mysql_error();
	}
	
	function Query ($query) {
		if ($result = mysql_query($query)) {
			return $result;
		} else {
			dp('<p>MySQL Error '. Database::GetErrorNumber() .': '. Database::GetError() .' in query <code>'. $query .'</code></p>');
			dp();
			return false;
		}
	}
	
	function GetLastInsertID () {
		return mysql_insert_id();
	}
	
	function GetModifiedRows () {
		// Code lifted from http://ca3.php.net/manual/en/function.mysql-info.php#65118
		$infoString = mysql_info();
		$affectedRows = mysql_affected_rows();
		ereg("Rows matched: ([0-9]*)", $infoString, $rowsMatched);
		return ($affectedRows < 1) ? ($rowsMatched[1] ? $rowsMatched[1] : 0) : $affectedRows;
	}
	
	function Insert ($table, $params) {
		$queryArray[] = 'INSERT INTO';
		$queryArray[] = $table;
		$queryArray[] = 'SET';
		foreach ($params as $field => $value) {
			$valuesArray[] = $field ." = '". Database::Sanitize($value) ."'";
		}
		$queryArray[] = implode(', ', $valuesArray);
		$query = implode(' ', $queryArray);
		if (Database::Query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function Update ($table, $params, $where) {
		
		// Turn $params array into a string
		foreach ($params as $field => $value) {
			$valuesArray[] = $field ." = '". Database::Sanitize($value) ."'";
		}
		$values = implode(', ', $valuesArray);
		
		// Turn $where into a string (if it isn't already)
		$where = Database::GetWhereString($where);
		
		$query = 'UPDATE '. $table .' SET '. $values .' WHERE '. $where;
		if (Database::Query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function DeleteFrom ($table, $where) {
		$query = 'DELETE FROM '. $table .' WHERE '. Database::GetWhereString($where);
		return Database::Query($query) ? true : false;
	}

	function GetTables () {
		$query = 'SHOW TABLES';
		if ($result = Database::Query($query)) {
			while ($row = mysql_fetch_row($result)) {
				$tables[$row[0]] = $row[0];
			}
			if (is_array($tables)) {
				return $tables;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function CreateTable ($name, $params) {
		global $_JAM;
		foreach ($params as $field => $info) {
			if (is_array($info)) {
				// If given an array, use value of 'type' key
				$type = $info['type'];
				$default = $info['default'];
			} else {
				// If given anything else, use it directly
				$type = $info;
			}
			$defaultString = isset($default) ? ' DEFAULT '. $default : '';
			
			// Make sure given type exists before adding the field
			if ($_JAM->fieldTypes[$type]) {
				$fields[] = $field .' '. $_JAM->fieldTypes[$type] . $defaultString;
				if (is_array($info) && $info['relatedModule']) {
					// Add foreign keys
					$indexes[] = $field;
					$foreignFields[$field] = array(
						'table' => $info['relatedModule'],
						'deleteAction' => $info['relatedDeleteAction']
					);
				}
			}
		}
		$fieldDefinitions = implode(', ', $fields);
		if ($_JAM->projectConfig['databaseTableType'] == 'myisam') {
			// Foreign keys are discarded for MyISAM tables
			$query = 'CREATE TABLE IF NOT EXISTS '. $name .' ('. $fieldDefinitions .')';			
		} else {
			// Default is InnoDB
			if ($foreignFields) {
				$foreignKeysString = ', INDEX ('. implode(', ', $indexes) .')';
				foreach ($foreignFields as $field => $info) {
					$foreignKeysString .= ', FOREIGN KEY ('. $field .') REFERENCES '. $info['table'] .' (id)';
					switch ($info['deleteAction']) {
						case 'cascade':
							$deleteAction = 'CASCADE';
							break;
						case 'setnull':
							$deleteAction = 'SET NULL';
							break;
						case 'noaction':
							$deleteAction = 'NO ACTION';
							break;
						case 'restrict':
						default:
							$deleteAction = 'RESTRICT';
							break;
					}
					$foreignKeysString .= ' ON DELETE '. $deleteAction .' ON UPDATE CASCADE';
				}
			}
			$query = 'CREATE TABLE IF NOT EXISTS '. $name .' ('. $fieldDefinitions . $foreignKeysString .') ENGINE=INNODB';
		}
		return Database::Query($query) ? true : false;
	}

	function GetWhereString($where) {
		if (is_array($where)) {
			// Submitted data is an array
			foreach ($where as $condition) {
				if (is_array($condition)) {
					// OR block
					$conditionsArray[] = '(' . implode(' OR ', $condition) . ')';
				} else {
					// AND item
					$conditionsArray[] = $condition;
				}
			}
			return implode(' AND ', $conditionsArray);
		} else {
			// Submitted data is (presumably) already a string; return it untouched
			return $where;
		}
	}

	function Sanitize ($string) {
		// Sanitize database input
		if (!get_magic_quotes_gpc()) {
			return mysql_real_escape_string($string);
		} else {
			return $string;
		}
	}

}

?>
