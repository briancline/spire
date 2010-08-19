<?php

	/**
	 * MYSQL ABSTRACT RECORD - BASE CLASS
	 * written and maintained by Brian A. Cline, 2006
	 * 
	 * NOTES
	 * This class is designed to be used with MySQL tables that have a single
	 * autoincrement primary key column.
	 * 
	 */

	class Model
	{
		protected static $_tableName = '';
		protected static $_keyField = '';
		protected static $_excludeFromInsert = array();
		protected static $_excludeFromUpdate = array();
		protected static $_insertTimestampField = '';
		protected static $_updateTimestampField = '';
		protected static $_cachePrefix = '';
		
		protected static $_fieldTypes = array();
		protected static $_fieldDefaults = array();
		
		private $_recordExists = false;
		
		
		function __construct($id = false)
		{
			// Run a discovery of this table's columns and data types.
			$this->discover();
			
			if ($id !== false) {
				if (is_array($id)) {
					// We received an associative array, presumably from mysql_fetch_assoc
					$row = $id;
				}
				else {
					$row = false;
					// Check the cache first, if using one
					if (!empty(static::$_cachePrefix)) {
						$cacheResult = Cache::get(static::$_cachePrefix . $id);
						if ($cacheResult && is_array($cacheResult)) {
							$row = $cacheResult;
						}
					}
					
					// Check the database next
					if (!$row) {
						$safeId = mysql_real_escape_string($id);
						$res = Database::query(
							"SELECT * ".
							"FROM `". static::$_tableName ."` ".
							"WHERE `". static::$_keyField ."` = '$safeId'");
						
						if ($res && Database::getRowCount($res) == 1) {
							// Fetch the row from the DB.
							$row = Database::fetchMap($res);
							
							if (method_exists($this, 'recordLoaded')) {
								$this->recordLoaded($row);
							}
						}
					}
				}
				
				if (!empty($row)) {
					// We have data; populate members with it.
					
					$this->_recordExists = true;
					
					foreach ($row as $column => $value) {
						$this->$column = $value;
					}
				}
				else {
					return false;
				}
			}
			
			/**
			 * Make sure all child classes have the correct stuff set up.
			 * If not, end the world.
			 */
			
			if (static::$_tableName == '') {
				die(get_class($this) .' has no table name set.');
			}
			if (static::$_keyField == '') {
				die(get_class($this) .' has no primary key field set.');
			}
			
			if (method_exists($this, 'record_construct')) {
				$this->record_construct();
			}
		}
		
		
		function __destruct()
		{
			// Call our child destructor to perform any cleanup.
			if (method_exists($this, 'record_destruct')) {
				$this->record_destruct();
			}
		}
		
		
		/**
		 * isFieldExcluded:
		 * Check to see if we are excluding a field. This is designed to be
		 * used only by getInsertFieldList and getUpdateFieldList.
		 */
		function isFieldExcluded($fieldName, $checkArray)
		{
			/**
			 * If the field name begins with an underscore, it is assumed
			 * to be a piece of metadata for this class and should be ignored.
			 */
			return (
				   $fieldName[0] == '_' 
				|| in_array($fieldName, $checkArray) 
				|| $fieldName == static::$_updateTimestampField 
				|| $fieldName == static::$_insertTimestampField
			);
		}
		
		
		/**
		 * getId:
		 * Return the value of our primary key column.
		 */
		function getId()
		{
			return $this->getKeyValue();
		}
		
		
		/**
		 * getKeyValue:
		 * Return the value of our primary key column.
		 */
		function getKeyValue()
		{
			$keyName = static::$_keyField;
			return $this->$keyName;
		}
		
		
		/**
		 * get:
		 * Return the value of a specific column.
		 */
		function get($fieldName)
		{
			return $this->$fieldName;
		}
		
		
		/**
		 * set:
		 * Sets the value of a column to the given value.
		 */
		function set($fieldName, $value)
		{
			$this->$fieldName = $value;
		}
		
		
		/**
		 * discover:
		 * Performs a quick discovery of all columns in the table, gathering
		 * and storing their names, data types, and default values.
		 */
		function discover()
		{
			if (!Config::get('model_table_discovery')) {
				return;
			}
			
			$cacheKey = static::$_tableName;
			if (!isset(static::$_fieldTypes[$cacheKey])) {
				$res = Database::query("SHOW COLUMNS IN `". static::$_tableName ."`");
				
				while ($row = Database::fetchMap($res)) {
					$fieldName = $row['Field'];
					$field_type = $row['Type'];
					$field_default = $row['Default'];
					
					/**
					 * We don't want to set default values to a string of 'NULL',
					 * just an empty string.
					 */
					if ($field_default == 'NULL') {
						$field_default = NULL;
					}
					
					static::$_fieldTypes[$cacheKey][$fieldName] = $field_type;
					static::$_fieldDefaults[$cacheKey][$fieldName] = $field_default;
				}
			}

			foreach (static::$_fieldDefaults[$cacheKey] as $fieldName => $defaultValue) {
				$this->$fieldName = $defaultValue;
			}
		}
		
		
		/**
		 * refresh:
		 * Refreshes or reloads the record from the database.
		 */
		function refresh()
		{
			$id = $this->getId();
			$res = Database::query(
				"SELECT * ".
				"FROM `". static::$_tableName ."` ".
				"WHERE `". static::$_keyField ."` = '$id'");
			
			if ($res && Database::getRowCount($res) == 1) {
				// Fetch the row from the DB.
				$row = Database::fetchMap($res);
				$this->_recordExists = true;
				
				if (method_exists($this, 'recordLoaded')) {
					$this->recordLoaded($row);
				}
				
				foreach ($row as $column => $value) {
					$this->$column = $value;
				}
			}
			else {
				// Nothing found; this empty record remains intact.
				$this->_recordExists = false;
			}
		}
		
		
		/**
		 * reload:
		 * Alias for refresh().
		 */
		function reload()
		{
			return $this->refresh();
		}
		
		
		/**
		 * fillFromPost:
		 * Reads the variables provided in $_POST and, if they are valid fields
		 * in our table, populate them accordingly.
		 */
		function fillFromPost()
		{
			$cacheKey = static::$_tableName;
			foreach ($_POST as $fieldName => $fieldValue) {
				if (array_key_exists($fieldName, static::$_fieldTypes[$cacheKey])) {
					$this->$fieldName = $fieldValue;
				}
			}
		}
		
		
		/**
		 * recordExists:
		 * Returns whether or not we think this record exists in the database.
		 */
		function recordExists()
		{
			return $this->_recordExists;
		}
		
		
		/**
		 * prepareClone:
		 * Clears the primary key value and resets the recordExists flag.
		 * This is to prepare this instance of the record to be duplicated
		 * in the table, with a new primary key.
		 */
		function prepareClone()
		{
			$key = static::$_keyField;
			
			$this->$key = '';
			$this->_recordExists = false;
		}
		
		
		/**
		 * resetMissingEnumValues:
		 * This function exists primarily when we need to use large sets of
		 * ENUM columns in the form of HTML input checkboxes on a site.
		 * An associative array of HTML form values (presumably acquired via
		 * $_POST or $_GET) can be passed as the first argument.
		 *
		 * This function will scan the associative array for all of our known ENUM
		 * columns to see if they exist; if not, we reset them to their default value
		 * as collected by discover().
		 * 
		 */
		function resetMissingEnumValues($assoc)
		{
			$cacheKey = static::$_tableName;
			foreach (static::$_fieldTypes[$cacheKey] as $fieldName => $type) {
				$checkName = $fieldName;
				
				// Skip any non-enum columns
				if (!preg_match('/^enum/i', $type)) {
					continue;
				}
				
				if (array_key_exists($checkName, $assoc) || in_array($checkName, $assoc)) {
					continue;
				}
					
				$this->$fieldName = static::$_fieldDefaults[$cacheKey][$fieldName];
			}
		}
		
		
		/**
		 * getUpdateFieldList:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the fields and values necessary to place into an
		 * INSERT statement.
		 */
		function getUpdateFieldList()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach ($fields as $fieldName => $value) {
				if (!$this->isFieldExcluded($fieldName, static::$_excludeFromUpdate)) {
					if (!empty($list)) {
						$list .= ', ';
					}
					
					$list .= "`$fieldName` = '". addslashes($value) ."'";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * getInsertFieldList:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the column names necessary to place into an
		 * INSERT statement.
		 */
		function getInsertFieldList()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach ($fields as $fieldName => $value) {
				if (!$this->isFieldExcluded($fieldName, static::$_excludeFromInsert)) {
					if (!empty($list)) {
						$list .= ', ';
					}
					
					$list .= "`$fieldName`";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * getInsertValueList:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the column values necessary to place into an
		 * INSERT statement.
		 */
		function getInsertValueList()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach ($fields as $fieldName => $value) {
				if (!$this->isFieldExcluded($fieldName, static::$_excludeFromInsert)) {
					if (!empty($list)) {
						$list .= ', ';
					}
					
					$list .= "'". addslashes($value) ."'";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * getHashMap:
		 * Returns a hash (associative array) of all members and their respective values, 
		 * much like what we would expect from a call to mysql_fetch_assoc().
		 */
		function getHashMap()
		{
			$fields = get_object_vars($this);
			$map = array();
			
			foreach ($fields as $fieldName => $value) {
				if ($fieldName[0] == '_') {
					continue;
				}
				
				$map[$fieldName] = $value;
			}
			
			return $map;
		}
		
		
		/**
		 * save:
		 * Through some internal logic, decides whether or not we need to perform an
		 * INSERT or an UPDATE for this record, then performs that query.
		 * This effectively saves the row to the database, regardless of its prior state.
		 */
		function save($log = false)
		{
			$keyName = static::$_keyField;
			$keyValue = addslashes($this->getKeyValue());
			
			if (!$this->recordExists()) {
				$fields = $this->getInsertFieldList();
				$values = $this->getInsertValueList();
				
				if (!empty(static::$_insertTimestampField)) {
					// Since we want to keep track of INSERT timestamps, generate one.
					$fields .= ', `'. static::$_insertTimestampField .'`';
					$values .= ", NOW()";
				}
				if (!empty(static::$_updateTimestampField)) {
					// Since we want to keep track of UPDATE timestamps, generate one.
					$fields .= ', `'. static::$_updateTimestampField .'`';
					$values .= ", NOW()";
				}
				
				Database::query("INSERT INTO `". static::$_tableName ."` ($fields) VALUES ($values)", $log);
				$this->$keyName = Database::getInsertId();
				$this->_recordExists = true;
				
				if (!empty(static::$_cachePrefix)) {
					Cache::dirty(static::$_cachePrefix . $this->getId());
				}
				
				if (method_exists($this, 'recordSaved')) {
					$this->recordSaved();
				}
			}
			else {
				$fields = $this->getUpdateFieldList();
				
				if (!empty(static::$_updateTimestampField)) {
					// Since we want to keep track of UPDATE timestamps, generate one.
					
					if (!empty($fields)) {
						$fields .= ', ';
					}
					
					$fields .= '`'. static::$_updateTimestampField .'` = NOW()';
				}
				
				Database::query("UPDATE `". static::$_tableName ."` SET $fields WHERE `$keyName` = '$keyValue'", $log);
				$this->_recordExists = true;

				if (!empty(static::$_cachePrefix)) {
					Cache::dirty(static::$_cachePrefix . $this->getId());
				}
				
				if (method_exists($this, 'recordSaved')) {
					$this->recordSaved();
				}
			}
		}
		
		
		/**
		 * previewSave:
		 * Sometimes during development we need to see the exact query that is generated.
		 * This function uses debugging messages to show the query that would be generated
		 * and executed by save(), but without actually executing it against the database.
		 */
		function previewSave()
		{
			$keyName = static::$_keyField;
			$keyValue = addslashes($this->getKeyValue());
			
			if (!$this->recordExists()) {
				$fields = $this->getInsertFieldList();
				$values = $this->getInsertValueList();
				
				if (!empty(static::$_insertTimestampField)) {
					$fields .= ', `'. static::$_insertTimestampField .'`';
					$values .= ", NOW()";
				}
				
				Debug::info('[DB-PREVIEW] INSERT INTO `%s` (%s) VALUES (%s)', 
					static::$_tableName, $fields, $values);
			}
			else {
				$fields = $this->getUpdateFieldList();
				
				if (!empty(static::$_updateTimestampField)) {
					if (!empty($fields)) {
						$fields .= ', ';
					}
					
					$fields .= '`'. static::$_updateTimestampField .'` = NOW()';
				}
				
				Debug::info("[DB-PREVIEW] UPDATE `%s` SET %s WHERE `%s` = '%s'", 
					static::$_tableName, $fields, $keyName, $keyValue);
			}
		}
		

		/**
		 * delete:
		 * Removes the entire row from the table.
		 */
		function delete()
		{
			$keyValue = $this->getKeyValue();
			
			// Perform the delete only if we have a key value for this record.
			if ($keyValue != 0) {
				Database::query("DELETE FROM `". static::$_tableName ."` WHERE `". static::$_keyField ."` = '$keyValue'");
			}
			
			if (!empty(static::$_cachePrefix)) {
				Cache::dirty(static::$_cachePrefix . $this->getId());
			}
			
			$this->_recordExists = false;
		}
		
		
		/**
		 * find:
		 * Locates records in this table meeting the criteria supplied in the
		 * first argument (an associative array of column names and their expected values).
		 */
		static function find($criteria = array(), $sort = false, $limitStart = false, $limitEnd = false, $cacheSingleRowOnLoad = false)
		{
			$q = "SELECT * FROM `". static::$_tableName ."`";
			
			$whereBits = array();
			foreach ($criteria as $column => $value) {
				$operand = '=';
				if (preg_match('/^(.+):(.+)$/', $column, $bits)) {
					$column = addslashes($bits[1]);
					$operandWord = $bits[2];
					
					switch($operandWord) {
						case 'ne':   $operand = '!=';   break;
						case 'gt':   $operand = '>';    break;
						case 'gte':  $operand = '>=';   break;
						case 'lt':   $operand = '<';    break;
						case 'lte':  $operand = '<=';   break;
						case 'like': $operand = 'LIKE'; break;
					}
				}
				
				$value = addslashes($value);
				$whereBits[] = "`$column` $operand '$value'";
			}
			
			if (!empty($whereBits)) {
				$q .= " WHERE ". implode(' AND ', $whereBits);
			}
			
			if ($sort && !is_array($sort)) {
				$sort = array($sort);
			}
			
			if ($sort) {
				$sortBits = array();
				foreach ($sort as $field) {
					if (preg_match('/^(.*):(.*)$/', $field, $bits)) {
						$order = strtoupper($bits[2]);
						if ($order != 'DESC' && $order != 'ASC') {
							$order = '';
						}
						
						$field = "`{$bits[1]}` {$order}";
					}
					else {
						$field = "`$field`";
					}
					
					$sortBits[] = $field;
				}
				
				$q .= ' ORDER BY '. implode(', ', $sortBits);
			}
			
			if ($limitStart !== false && is_numeric($limitStart)) {
				$q .= " LIMIT $limitStart";
				
				if ($limitEnd !== false && is_numeric($limitEnd) && $limitEnd > 0) {
					$q .= ", $limitEnd";
				}
			}
			
			$set = Database::query($q);
			if (!$set) {
				return false;
			}
			
			$className = get_called_class();
			$results = array();
			$lastRow = false;
			while ($row = Database::fetchMap($set)) {
				$results[] = new $className($row);
				$lastRow = $row;
			}
			
			if (count($results) == 0) {
				$results = false;
			}
			elseif (count($results) == 1) {
				$results = $results[0];
				
				if ($cacheSingleRowOnLoad && !empty(static::$_cachePrefix)) {
					Cache::set(static::$_cachePrefix . $results->getId(), $lastRow);
				}
			}
			
			return $results;
		}

		/**
		 * findById:
		 * Performs a generic search based solely on the primary key field.
		 */
		public static function findById($id, $sort = false, $limitStart = false, $limitEnd = false)
		{
			if (!empty(static::$_cachePrefix)) {
				$cacheResult = Cache::get(static::$_cachePrefix . $id);
				if ($cacheResult && is_array($cacheResult)) {
					$className = get_called_class();
					return new $className($cacheResult);
				}
			}
			
			return self::find(array(static::$_keyField => $id), $sort, $limitStart, $limitEnd, true);
		}

		/**
		 * findAll:
		 * Requests every record in the table, passing the sorting and limiting criteria to
		 * find(). Returns an array of objects, even if there is only one. If no results were
		 * returned, returns an empty array.
		 */
		public static function findAll($sort = false, $limitStart = false, $limitEnd = false)
		{
			return self::findSet(array(), $sort, $limitStart, $limitEnd);
		}

		/**
		 * findSet:
		 * Passes the request to find() and returns the result as an array of objects,
		 * even if there is only one. If no results were returned, return an empty array.
		 */
		public static function findSet($criteria = array(), $sort = false, $limitStart = false, $limitEnd = false)
		{
			$results = self::find($criteria, $sort, $limitStart, $limitEnd);

			if (is_array($results)) {
				return $results;
			}
			elseif ($results) {
				return array($results);
			}

			return array();
		}
	}
