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
		var $_table_name = 'unknown';
		var $_key_field = 'unknown';
		var $_field_types = array();
		var $_field_default = array();
		var $_exclude_from_insert = array();
		var $_exclude_from_update = array();
		var $_update_timestamp_field = '';
		var $_insert_timestamp_field = '';
		var $_record_exists = false;
		
		
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
					
					$row = 0;
					
					if($this->using_memcache())
					{
						$cache_key = sprintf("DB/%s/%s",
							$this->_table_name,
							$id);
						$ser_record = Memcache::get($cache_key);
						
						if($ser_record == '')
							return false;
						
						$row = unserialize($ser_record);
					}
					
					// Query the DB if we're not using memcache, or if we are and nothing was returned.
					if(!is_array($row))
					{
						$res = Database::query(
							"select * ".
							"from `$this->_table_name` ".
							"where `$this->_key_field` = '$id'");
						
						if($res && Database::num_rows($res) == 1)
						{
							// Fetch the row from the DB.
							$row = Database::fetch_assoc($res);
						}
						else
						{
							// Nothing found; this empty record remains intact.
							return false;
						}
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
			
			if($this->_table_name == 'unknown')
				die(get_class($this) .' has no table name set.');
			if($this->_key_field == 'unknown')
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
		
		
		function using_memcache()
		{
			return Config::get('memcache_enabled');
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
				|| $field == $this->_update_timestamp_field 
				|| $field == $this->_insert_timestamp_field
			);
		}
		
		
		/**
		 * get_id:
		 * Return the value of our primary key column.
		 */
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
			$key_name = $this->_key_field;
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
			
			$res = Database::query("show columns in `$this->_table_name`");
			
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
					$field_default = '';
				
				$this->_field_types[$field_name] = $field_type;
				$this->_field_defaults[$field_name] = $field_default;
				
				// Go ahead and set this column's default value in memory.
				$this->$field_name = $field_default;
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
				"from `$this->_table_name` ".
				"where `$this->_key_field` = '$id'");
			
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
				if(array_key_exists($field_name, $this->_field_types))
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
			$key = $this->_key_field;
			
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
			foreach($this->_field_types as $field_name => $type)
			{
				$check_name = $field_name;
				
				// Skip any non-enum columns
				if(!eregi('^enum', $type))
					continue;
				
				if(array_key_exists($check_name, $assoc) || in_array($check_name, $assoc))
					continue;
					
				$this->$field_name = $this->_field_defaults[$field_name];
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
				if(!$this->is_field_excluded($field, $this->_exclude_from_update))
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
				if(!$this->is_field_excluded($field, $this->_exclude_from_insert))
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
				if(!$this->is_field_excluded($field, $this->_exclude_from_insert))
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
			$key_name = $this->_key_field;
			$key_value = addslashes($this->get_key_value());
			
			if(!$this->record_exists())
			{
				$fields = $this->get_insert_fieldlist();
				$values = $this->get_insert_valuelist();
				
				if(!empty($this->_insert_timestamp_field))
				{
					// Since we want to keep track of INSERT timestamps, generate one.
					$fields .= ', `'. $this->_insert_timestamp_field .'`';
					$values .= ", NOW()";
				}
				
				Database::query("insert into `$this->_table_name` ($fields) values ($values)", $log);
				$this->$key_name = Database::insert_id();
				$this->_record_exists = true;
			}
			else
			{
				$fields = $this->get_update_fieldlist();
				
				if(!empty($this->_update_timestamp_field))
				{
					// Since we want to keep track of UPDATE timestamps, generate one.
					
					if(!empty($fields))
						$fields .= ', ';
					
					$fields .= '`'. $this->_update_timestamp_field .'` = NOW()';
				}
				
				Database::query("update `$this->_table_name` set $fields where `$key_name` = '$key_value'", $log);
				$this->_record_exists = true;
			}

			if($this->using_memcache())
			{
				$field_array = $this->get_assoc();
				$cache_key = sprintf("DB/%s/%s",
					$this->_table_name,
					$this->get_key_value());
				$cache_data = serialize($field_array);
				
				Memcache::set($cache_key, $cache_data);
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
			$key_name = $this->_key_field;
			$key_value = addslashes($this->get_key_value());
			
			if(!$this->record_exists())
			{
				$fields = $this->get_insert_fieldlist();
				$values = $this->get_insert_valuelist();
				
				if(!empty($this->_insert_timestamp_field))
				{
					$fields .= ', `'. $this->_insert_timestamp_field .'`';
					$values .= ", NOW()";
				}
				
				Debug::infof('[DB-PREVIEW] insert into `%s` (%s) values (%s)', 
					$this->_table_name, $fields, $values);
			}
			else
			{
				$fields = $this->get_update_fieldlist();
				
				if(!empty($this->_update_timestamp_field))
				{
					if(!empty($fields))
						$fields .= ', ';
					
					$fields .= '`'. $this->_update_timestamp_field .'` = NOW()';
				}
				
				Debug::infof("[DB-PREVIEW] update %s set %s where `%s` = '%s'", 
					$this->_table_name, $fields, $key_name, $key_value);
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
				Database::query("delete from `$this->_table_name` where `$this->_key_field` = '$key_value'");
			
			$this->_record_exists = false;
			
			if($this->using_memcache())
			{
				$cache_key = sprintf("DB/%s/%s",
					$this->_table_name,
					$key_value);
				
				Memcache::set($cache_key, '');
			}
		}
		
		
		/**
		 * find:
		 * Locates records in this table meeting the criteria supplied in the
		 * first argument (an associative array of column names and their expected values).
		 */
		static function find($class_name, $table_name, $criteria, $sort = false)
		{
			$where_bits = array();
			foreach($criteria as $column => $value)
			{
				$value = addslashes($value);
				$where_bits[] = "`$column` = '$value'";
			}
			
			$q = "select * from `$table_name` where ". implode(' and ', $where_bits);
			
			if($sort && !is_array($sort)) {
				$sort = array($sort);
			}
			
			if($sort)
			{
				$sort_bits = array();
				foreach($sort as $field)
					$sort_bits[] = $field;
				
				$q .= ' order by '. implode(', ', $sort_bits);
			}
			
			$set = Database::query($q);
			if(!$set)
				return false;
			
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
	}
	
?>