<?php

	class Database
	{
		private static $tableMetaDataCache = array();
		
		
		static function datetime($ts_or_date = -1, $secs_delta = 0)
		{
			$format = 'Y-m-d H:i:s';
			$ts = 0;
			
			if(is_numeric($ts_or_date))
				$ts = $ts_or_date + $secs_delta;
			elseif($ts_or_date != -1)
				$ts = strtotime($ts_or_date) + $secs_delta;
			else
				$ts = time();
			
			return date($format, $ts);
		}
		
		
		function connect($host, $user, $pass, $database = '')
		{
			$conn = mysql_connect($host, $user, $pass);
			
			if($conn && $database)
				mysql_select_db($database, $conn);
			
			return $conn;
		}
		
		/**
		 * Run a query against the global MySQL connection.
		 *
		 * @param string $query
		 * @param boolean $log
		 * @return MySQL Result
		 */
		function query($query, $log = false)
		{
			$res = mysql_query($query);
			$err = mysql_errno();
			
			if($err != 0)
			{
				Debug::trace("[QUERY] $query");
				Debug::critical("[ERROR] ".
					"I'm sorry, Dave, I'm afraid I can't do that. ".
					mysql_error());
			}
			elseif($log)
			{
				Debug::trace("[QUERY] $query");
			}
			
			return $res;
		}
		
		function query_record($query)
		{
			$res = Database::query($query);
			$rec = mysql_fetch_assoc($res);
			
			return $rec;
		}
		
		function query_value($query, $col = 0)
		{
			$res = Database::query($query);
			$val = @mysql_result($res, $col);
			
			return $val;
		}
		
		function query_object($query)
		{
			$row = Database::query_record($query);
			return Database::row_to_obj($row);
		}
		
		function query_id_array($query, $log = false)
		{
			$res = Database::db_query($query, $log);
			$err = mysql_errno();
			$arr = false;
			
			if($res)
			{
				$arr = array();
				
				while($rec = mysql_fetch_array($res))
				{
					$arr[] = $rec[0];
				}
			}
			
			return $arr;
		}
		
		function insert_id($result = 0)
		{
			if($result != 0)
				return mysql_insert_id($result);
			else
				return mysql_insert_id();
		}
		
		function num_rows($result)
		{
			return mysql_num_rows($result);
		}
		
		function fetch_assoc($result)
		{
			return mysql_fetch_assoc($result);
		}
		
		function fetch_object($result)
		{
			$row = mysql_fetch_assoc($result);
			return Database::row_to_obj($row);
		}
		
		function row_to_obj($row)
		{
			if(!$row)
				return false;
			
			$obj = new stdClass();
			
			foreach($row as $key => $value)
				$obj->$key = $value;
				
			return $obj;
		}
		
		function close($conn = 0)
		{
			if($conn != 0)
				mysql_close($conn);
			else
				mysql_close();
		}
		
		public function cacheTableMetaData($tableName, $types, $defaults)
		{
			$cacheEntry = new DatabaseTableMetaCacheEntry($types, $defaults);

			$key = sprintf('%s:%s', Config::get('db_database'), $tableName);
			self::$tableMetaDataCache[$key] = $cacheEntry;
		}

		public function getCachedTableMetaData($tableName)
		{
			$key = sprintf('%s:%s', Config::get('db_database'), $tableName);

			if(!isset(self::$tableMetaDataCache[$key])) {
				return false;
			}

			return self::$tableMetaDataCache[$key];
		}
	}


	class DatabaseTableMetaCacheEntry
	{
		public $types = array();
		public $defaults = array();

		public function DatabaseTableMetaCacheEntry($types, $defaults)
		{
			$this->types = $types;
			$this->defaults = $defaults;
		}
	}

?>
