<?

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
	
	function GetAffectedRows () {
		return mysql_affected_rows();
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
		global $_JAG;
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
			if ($_JAG['fieldTypes'][$type]) {
				$fields[] = $field .' '. $_JAG['fieldTypes'][$type] . $defaultString;
			}
		}
		$fieldDefinitions = implode(', ', $fields);
		$query = 'CREATE TABLE IF NOT EXISTS '. $name .' ('. $fieldDefinitions .')';
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
