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
		protected static $_table_name = '';
		protected static $_key_field = '';
		protected static $_exclude_from_insert = array();
		protected static $_exclude_from_update = array();
		protected static $_insert_timestamp_field = '';
		protected static $_update_timestamp_field = '';
		
		protected static $_field_types = array();
		protected static $_field_defaults = array();
		
		private $_record_exists = false;
		
		
		function __construct($id = 0)
		{
			// Run a discovery of this table's columns and data types.
			$this->discover();
			
			if($id > 0)
			{
				if(is_array($id))
				{
					// We received an associative array, presumably from mysql_fetch_assoc
					$row = $id;
				}
				else
				{
					// We received an ID, so check the database for it
					
					$res = Database::query(
						"select * ".
						"from `". static::$_table_name ."` ".
						"where `". static::$_key_field ."` = '$id'");
					
					if($res && Database::num_rows($res) == 1) {
						// Fetch the row from the DB.
						$row = Database::fetch_assoc($res);
					}
					else {
						// Nothing found; this empty record remains intact.
						return false;
					}
				}
				
				if(!empty($row))
				{
					// We have data; populate members with it.
					
					$this->_record_exists = true;
					
					foreach($row as $column => $value)
						$this->$column = $value;
				}
			}
			
			/**
			 * Make sure all child classes have the correct stuff set up.
			 * If not, end the world.
			 */
			
			if(static::$_table_name == '')
				die(get_class($this) .' has no table name set.');
			if(static::$_key_field == '')
				die(get_class($this) .' has no primary key field set.');
			
			if(method_exists($this, 'record_construct'))
				$this->record_construct();
		}
		
		
		function __destruct()
		{
			// Call our child destructor to perform any cleanup.
			if(method_exists($this, 'record_destruct'))
				$this->record_destruct();
		}
		
		
		/**
		 * is_field_excluded:
		 * Check to see if we are excluding a field. This is designed to be
		 * used only by get_insert_fieldlist and get_update_fieldlist.
		 */
		function is_field_excluded($field, $check_array)
		{
			/**
			 * If the field name begins with an underscore, it is assumed
			 * to be a piece of metadata for this class and should be ignored.
			 */
			return (
				   $field[0] == '_' 
				|| in_array($field, $check_array) 
				|| $field == static::$_update_timestamp_field 
				|| $field == static::$_insert_timestamp_field
			);
		}
		
		
		/**
		 * get_id:
		 * Return the value of our primary key column.
		 */
		function getId() { return $this->get_id(); }
		function get_id()
		{
			return $this->get_key_value();
		}
		
		
		/**
		 * get_key_value:
		 * Return the value of our primary key column.
		 */
		function get_key_value()
		{
			$key_name = static::$_key_field;
			return $this->$key_name;
		}
		
		
		/**
		 * get:
		 * Return the value of a specific column.
		 */
		function get($field)
		{
			return $this->$field;
		}
		
		
		/**
		 * set:
		 * Sets the value of a column to the given value.
		 */
		function set($field, $value)
		{
			$this->$field = $value;
		}
		
		
		/**
		 * discover:
		 * Performs a quick discovery of all columns in the table, gathering
		 * and storing their names, data types, and default values.
		 */
		function discover()
		{
			if(!Config::get('model_table_discovery')) {
				return;
			}
			
			$cache_key = static::$_table_name;
			if(!isset(static::$_field_types[$cache_key]))
			{
				$res = Database::query("show columns in `". static::$_table_name ."`");
				
				while($row = Database::fetch_assoc($res))
				{
					$field_name = $row['Field'];
					$field_type = $row['Type'];
					$field_default = $row['Default'];
					
					/**
					 * We don't want to set default values to a string of 'NULL',
					 * just an empty string.
					 */
					if($field_default == 'NULL')
						$field_default = NULL;
					
					static::$_field_types[$cache_key][$field_name] = $field_type;
					static::$_field_defaults[$cache_key][$field_name] = $field_default;
				}
			}

			foreach(static::$_field_defaults[$cache_key] as $fieldName => $defaultValue) {
				$this->$fieldName = $defaultValue;
			}
		}
		
		
		/**
		 * refresh:
		 * Refreshes or reloads the record from the database.
		 */
		function refresh()
		{
			$id = $this->get_id();
			$res = Database::query(
				"select * ".
				"from `". static::$_table_name ."` ".
				"where `". static::$_key_field ."` = '$id'");
			
			if($res && Database::num_rows($res) == 1)
			{
				// Fetch the row from the DB.
				$row = Database::fetch_assoc($res);
				$this->_record_exists = true;
				
				foreach($row as $column => $value)
					$this->$column = $value;
			}
			else
			{
				// Nothing found; this empty record remains intact.
				$this->_record_exists = false;
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
		 * fill_from_post:
		 * Reads the variables provided in $_POST and, if they are valid fields
		 * in our table, populate them accordingly.
		 */
		function fill_from_post()
		{
			foreach($_POST as $field_name => $field_value)
			{
				if(array_key_exists($field_name, static::$_field_types))
					$this->$field_name = $field_value;
			}
		}
		
		
		/**
		 * record_exists:
		 * Returns whether or not we think this record exists in the database.
		 */
		function record_exists()
		{
			return $this->_record_exists;
		}
		
		
		/**
		 * prepare_clone:
		 * Clears the primary key value and resets the record_exists flag.
		 * This is to prepare this instance of the record to be duplicated
		 * in the table, with a new primary key.
		 */
		function prepare_clone()
		{
			$key = static::$_key_field;
			
			$this->$key = '';
			$this->_record_exists = false;
		}
		
		
		/**
		 * reset_missing_enums:
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
		function reset_missing_enums($assoc)
		{
			foreach(static::$_field_types as $field_name => $type)
			{
				$check_name = $field_name;
				
				// Skip any non-enum columns
				if(!eregi('^enum', $type))
					continue;
				
				if(array_key_exists($check_name, $assoc) || in_array($check_name, $assoc))
					continue;
					
				$this->$field_name = static::$_field_defaults[$field_name];
			}
		}
		
		
		/**
		 * get_update_fieldlist:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the fields and values necessary to place into an
		 * INSERT statement.
		 */
		function get_update_fieldlist()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach($fields as $field => $value)
			{
				if(!$this->is_field_excluded($field, static::$_exclude_from_update))
				{
					if(!empty($list))
						$list .= ', ';
					
					$list .= "`$field` = '". addslashes($value) ."'";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * get_insert_fieldlist:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the column names necessary to place into an
		 * INSERT statement.
		 */
		function get_insert_fieldlist()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach($fields as $field => $value)
			{
				if(!$this->is_field_excluded($field, static::$_exclude_from_insert))
				{
					if(!empty($list))
						$list .= ', ';
					
					$list .= "`$field`";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * get_insert_valuelist:
		 * Inspects all the local members, which we assume to be columns, then builds and
		 * returns a string containing the column values necessary to place into an
		 * INSERT statement.
		 */
		function get_insert_valuelist()
		{
			$fields = get_object_vars($this);
			$list = '';
			
			foreach($fields as $field => $value)
			{
				if(!$this->is_field_excluded($field, static::$_exclude_from_insert))
				{
					if(!empty($list))
						$list .= ', ';
					
					$list .= "'". addslashes($value) ."'";
				}
			}
			
			return $list;
		}
		
		
		/**
		 * get_assoc:
		 * Returns an associative array of all members and their respective values, much
		 * like what we would expect from a call to mysql_fetch_assoc().
		 */
		function get_assoc()
		{
			$fields = get_object_vars($this);
			$assoc = array();
			
			foreach($fields as $field => $value)
			{
				if($field[0] == '_')
					continue;
				
				$assoc[$field] = $value;
			}
			
			return $assoc;
		}
		
		
		/**
		 * save:
		 * Through some internal logic, decides whether or not we need to perform an
		 * INSERT or an UPDATE for this record, then performs that query.
		 * This effectively saves the row to the database, regardless of its prior state.
		 */
		function save($log = false)
		{
			$key_name = static::$_key_field;
			$key_value = addslashes($this->get_key_value());
			
			if(!$this->record_exists())
			{
				$fields = $this->get_insert_fieldlist();
				$values = $this->get_insert_valuelist();
				
				if(!empty(static::$_insert_timestamp_field))
				{
					// Since we want to keep track of INSERT timestamps, generate one.
					$fields .= ', `'. static::$_insert_timestamp_field .'`';
					$values .= ", NOW()";
				}
				
				Database::query("insert into `". static::$_table_name ."` ($fields) values ($values)", $log);
				$this->$key_name = Database::insert_id();
				$this->_record_exists = true;
			}
			else
			{
				$fields = $this->get_update_fieldlist();
				
				if(!empty(static::$_update_timestamp_field))
				{
					// Since we want to keep track of UPDATE timestamps, generate one.
					
					if(!empty($fields))
						$fields .= ', ';
					
					$fields .= '`'. static::$_update_timestamp_field .'` = NOW()';
				}
				
				Database::query("update `". static::$_table_name ."` set $fields where `$key_name` = '$key_value'", $log);
				$this->_record_exists = true;
			}
		}
		
		
		/**
		 * preview_save:
		 * Sometimes during development we need to see the exact query that is generated.
		 * This function uses debugging messages to show the query that would be generated
		 * and executed by save(), but without actually executing it against the database.
		 */
		function preview_save()
		{
			$key_name = static::$_key_field;
			$key_value = addslashes($this->get_key_value());
			
			if(!$this->record_exists())
			{
				$fields = $this->get_insert_fieldlist();
				$values = $this->get_insert_valuelist();
				
				if(!empty(static::$_insert_timestamp_field))
				{
					$fields .= ', `'. static::$_insert_timestamp_field .'`';
					$values .= ", NOW()";
				}
				
				Debug::infof('[DB-PREVIEW] insert into `%s` (%s) values (%s)', 
					static::$_table_name, $fields, $values);
			}
			else
			{
				$fields = $this->get_update_fieldlist();
				
				if(!empty(static::$_update_timestamp_field))
				{
					if(!empty($fields))
						$fields .= ', ';
					
					$fields .= '`'. static::$_update_timestamp_field .'` = NOW()';
				}
				
				Debug::info("[DB-PREVIEW] update %s set %s where `%s` = '%s'", 
					static::$_table_name, $fields, $key_name, $key_value);
			}
		}
		

		/**
		 * delete:
		 * Removes the entire row from the table.
		 */
		function delete()
		{
			$key_value = $this->get_key_value();
			
			// Perform the delete only if we have a key value for this record.
			if($key_value != 0)
				Database::query("delete from `". static::$_table_name ."` where `". static::$_key_field ."` = '$key_value'");
			
			$this->_record_exists = false;
		}
		
		
		/**
		 * find:
		 * Locates records in this table meeting the criteria supplied in the
		 * first argument (an associative array of column names and their expected values).
		 */
		static function find($criteria = array(), $sort = false, $limitStart = false, $limitEnd = false)
		{
			$q = "SELECT * FROM `". static::$_table_name ."`";

			$where_bits = array();
			foreach($criteria as $column => $value)
			{
				$operand = '=';
				if(preg_match('/^(.+):(.+)$/', $column, $bits)) {
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
				$where_bits[] = "`$column` $operand '$value'";
			}
			
			if(!empty($where_bits)) {
				$q .= " WHERE ". implode(' AND ', $where_bits);
			}
			
			if($sort && !is_array($sort)) {
				$sort = array($sort);
			}
			
			if($sort)
			{
				$sort_bits = array();
				foreach($sort as $field)
				{
					if(preg_match('/^(.*):(.*)$/', $field, $bits))
					{
						$order = strtoupper($bits[2]);
						if($order != 'DESC' && $order != 'ASC') {
							$order = '';
						}
						
						$field = "`{$bits[1]}` {$order}";
					}
					else {
						$field = "`$field`";
					}
					
					$sort_bits[] = $field;
				}
				
				$q .= ' ORDER BY '. implode(', ', $sort_bits);
			}
			
			if($limitStart !== false && is_numeric($limitStart)) {
				$q .= " LIMIT $limitStart";
				
				if($limitEnd !== false && is_numeric($limitEnd)) {
					$q .= ", $limitEnd";
				}
			}
			
			$set = Database::query($q);
			if(!$set)
				return false;
			
			$class_name = get_called_class();
			$results = array();
			while($row = Database::fetch_assoc($set)) {
				$results[] = new $class_name($row);
			}
			
			if(count($results) == 0) {
				$results = false;
			}
			elseif(count($results) == 1) {
				$results = $results[0];
			}
			
			return $results;
		}

		/**
		 * findById:
		 * Performs a generic search based on the primary key field.
		 */
		public static function findById($id, $sort = false, $limitStart = false, $limitEnd = false)
		{
			return self::find(array(static::$_key_field => $id), $sort, $limitStart, $limitEnd);
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
		public static function findSet($criteria = array(), $sort = false, $limitStart = alse, $limitEnd = false)
		{
			$results = self::find($criteria, $sort, $limitStart, $limitEnd);

			if(is_array($results)) {
				return $results;
			}
			elseif($results) {
				return array($results);
			}

			return array();
		}
	}
	
?>
