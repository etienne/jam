<?php

require_once('classes/Arrays.php');
require_once('classes/Database.php');

class Query {

	var $fields = array();
	var $from = array();
	var $join = array();
	var $where = array();
	var $groupby = array();
	var $orderby = array();
	var $limit;
	var $offset;
	var $result;
	
	/*
	 * Constructor
	 */
	
	function Query($queryParams = false) {
		if ($queryParams) {
			$this->LoadParameters($queryParams);
		}
	}
	
	/*
	 * Static
	 */

	function SingleValue ($table, $field, $where) {
		// Single row, single field
		$queryParams = array(
			'fields' => $field,
			'from' => $table,
			'where' => $where,
			'limit' => 1
		);
		$query = new Query($queryParams);
		return $query->GetSingleValue();
	}
	
	function SingleRow ($table, $where) {
		$queryParams = array(
			'from' => $table,
			'where' => $where,
			'limit' => 1
		);
		$query = new Query($queryParams);
		if ($rows = $query->GetArray()) {
			return current($rows);
		} else {
			return false;
		}
	}
	
	function SingleColumn ($table, $field, $where = '', $orderBy = '') {
		// FIXME: UNTESTED
		$queryParams = array(
			'from' => $table,
			'fields' => $field,
			'where' => $where,
			'orderBy' => $orderBy
		);
		$query = new Query($queryParams);
		if ($rows = $query->GetArray()) {
			$field = array();
			foreach($rows as $row) {
				$field[] = current($row);
			}
			return $field;
		} else {
			return false;
		}
	}
	
	function SimpleResults ($table, $fields = '', $where = '') {
		// Two fields
		$queryParams = array(
			'fields' => $fields,
			'from' => $table,
			'where' => $where
		);
		$query = new Query($queryParams);
		return $query->GetSimpleArray();
	}
	
	function FullResults ($table, $where = '') {
		$queryParams = array(
			'from' => $table,
			'where' => $where
		);
		$query = new Query($queryParams);
		return $query->GetArray();
	}
	
	function TableIsEmpty ($table) {
		$queryParams = array('from' => $table);
		$query = new Query($queryParams);
		$numRows = $query->NumRows();
		if ($numRows) {
			return false;
		} else {
			return true;
		}
	}

	/*
	 * Private
	 */

	function GetFields() {
		if ($this->fields) {
			foreach ($this->fields as $alias => $field) {
				if ($alias == $field) {
					// Alias is the same as field so we don't need it
					$fieldsArray[] = $field;
				} else {
					// Use alias
					$fieldsArray[] = $field .' AS '. $alias;
				}
			}
			return implode(', ', $fieldsArray);
		} else {
			return '*';
		}
	}

	function GetFrom() {
		if ($this->from) {
			foreach ($this->from as $fromTable) {
				if ($this->join[$fromTable]) {
					foreach($this->join[$fromTable] as $joinInfo) {
						$join = ' LEFT JOIN ('. $joinInfo['tables'] .')';
						$condition = ' ON ('. implode(' AND ', $joinInfo['joinConditions']) .')';
						$joins[] = $join . $condition;
					}
					$from[] = $fromTable . implode(' ', $joins);
				} else {
					$from[] = $fromTable;
				}
			}
			return 'FROM '. implode(', ', $from);
		} else {
			return false;
		}
	}
	
	function GetWhere() {
		if ($this->where) {
			$whereString = Database::GetWhereString($this->where);
			return 'WHERE '. $whereString;
		} else {
			return false;
		}
	}

	function GetGroupBy() {
		return $this->groupby ? ('GROUP BY '. implode(', ', $this->groupby)) : false;
	}
	
	function GetOrderBy() {
		return $this->orderby ? ('ORDER BY '. implode(', ', $this->orderby)) : false;
	}
	
	function GetLimit() {
		return $this->limit ? ('LIMIT '. $this->limit) : false;
	}
	
	function GetOffset() {
		return $this->offset ? ('OFFSET '. $this->offset) : false;
	}
	
	function GetQueryString() {
		$queryArray = array(
			'SELECT',
			$this->GetFields(),
			$this->GetFrom(),
			$this->GetWhere(),
			$this->GetGroupBy(),
			$this->GetOrderBy(),
			$this->GetLimit(),
			$this->GetOffset()
		);
		
		// Join query into a string
		return implode(' ', $queryArray);
	}
	
	function GetResult() {
		if ($this->result) {
			return $this->result;
		} else {
			// Run query
			$query = $this->GetQueryString();
			return $this->result = Database::Query($query);
		}
	}
	
	/*
	 * Public
	 */

	function LoadParameters($params) {
		if (is_array($params)) {
			foreach ($params as $name => $param) {
				if ($param) {
					switch ($name) {
						case 'fields': $this->AddFields($param); break;
						case 'from': $this->AddFrom($param); break;
						case 'where': $this->AddWhere($param); break;
						case 'join': $this->AddJoin($param['on'], $param['table'], $param['condition']); break;
						case 'groupby': $this->AddGroupBy($param); break;
						case 'orderby': $this->AddOrderBy($param); break;
						case 'limit': $this->AddLimit($param); break;
						case 'offset': $this->AddOffset($param); break;
					}
				}
			}
			return true;
		} else {
			return false;
		}
	}
	
	function AddFields($fields) {
		// Turn into an array if it isn't already
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		
		foreach ($fields as $alias => $field) {
			if (is_string($alias)) {
				// Use alias if there is one (cave.machin AS roger)
				$this->fields[$alias] = $field;
			} else {
				// Use field instead of alias if no alias was given
				$this->fields[$field] = $field;
			}
		}
	}
	
	function AddFrom($from) {
		Arrays::Add($this->from, $from);
	}
	
	function AddJoin($table, $joinTable, $joinConditions) {
		// TODO: We only support left joins for now
		$joinArray = array();
		$joinArray['tables'] = $joinTable;
		if (is_array($joinConditions)) {
			foreach($joinConditions as $condition) {
				$joinArray['joinConditions'][] = $condition;
			}
		} else {
			$joinArray['joinConditions'][] = $joinConditions;
		}
		$this->join[$table][] = $joinArray;
	}
	
	function AddWhere($where) {
		Arrays::Add($this->where, $where);
	}
	
	function AddGroupBy($groupby) {
		Arrays::Add($this->groupby, $groupby);
	}
	
	function AddOrderBy($orderby) {
		Arrays::Add($this->orderby, $orderby);
	}
	
	function AddLimit($limit) {
		$this->limit = $limit;
	}
	
	function AddOffset($offset) {
		$this->offset = $offset;
	}
	
	function FetchArray() {
		/* Wrapper for mysql_fetch_array() */
		return mysql_fetch_array($this->GetResult());
	}

	function FetchAssoc() {
		/* Wrapper for mysql_fetch_assoc() */
		return mysql_fetch_assoc($this->GetResult());
	}

	function NumRows() {
		/* Wrapper for mysql_num_rows() */
		return mysql_num_rows($this->GetResult());
	}
	
	function GetPrimaryKeyColumn() {
		if ($result = $this->GetResult()) {
			while ($fieldInfo = mysql_fetch_field($this->GetResult())) {
				/* FIXME: We should never check for field name here, this is a workaround to a strange bug where
				the object returned by mysql_fetch_field() does not correctly report fields as primary key. */
				if ($fieldInfo->primary_key || $fieldInfo->name == 'id') {
					return $fieldInfo->name;
				}
			}
		}
		return false;
	}

	function GetSingleValue() {
		/* Returns the first value of the first row. Assumes a single-row,
		single-field query. */
		
		$array = $this->FetchArray();
		return $array[0];
	}

	function GetSimpleArray() {
		/* Puts the result of a simple 2-fields query into an array using the
		first field as the index. */
		
		// Make sure we have exactly two fields
		if (mysql_num_fields($this->GetResult()) != 2) {
			dp($this->GetQueryString());
			dp();
			trigger_error('Query::GetSimpleArray() requires a query with exactly two fields', E_USER_WARNING);
			return false;
		}
		
		// Build array
		while ($row = mysql_fetch_row($this->GetResult())) {
			$array[$row[0]] = $row[1];
		}

		return $array;
	}

	function GetArray() {
		/* Puts the result of a complex (multi-field) query into an array.
		The result is an array containg arrays for each result row. If there
		is a primary key field, its value is used as the index for the 
		parent array; otherwise we don't specify an index. */
		
		// Get primary key field name if applicable
		$primaryKeyColumn = $this->GetPrimaryKeyColumn();
		
		// Build array
		$result = $this->GetResult();
		if ($result) {
			while ($array = mysql_fetch_assoc($result)) {
				if ($primaryKeyColumn) {
					$returnArray[$array[$primaryKeyColumn]] = $array;
				} else {
					$returnArray[] = $array;
				}
			}
			return $returnArray;
		} else {
			return false;
		}
	}

}

?>
