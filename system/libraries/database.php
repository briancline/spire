<?php

	class Database
	{
		public static $queryCount = 0;
		
		static function datetime($tsOrDate = -1, $secsDelta = 0)
		{
			$format = 'Y-m-d H:i:s';
			$ts = 0;
			
			if (is_numeric($tsOrDate) && $tsOrDate >= 0) {
				$ts = $tsOrDate + $secsDelta;
			}
			elseif ($tsOrDate != -1) {
				$ts = strtotime($tsOrDate) + $secsDelta;
			}
			else {
				$ts = time();
			}
			
			return date($format, $ts);
		}
		
		
		function connect($host, $user, $pass, $database = '')
		{
			$conn = mysql_connect($host, $user, $pass);
			
			if ($conn && $database) {
				mysql_select_db($database, $conn);
			}
			
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
			self::$queryCount++;
			
			if ($err != 0) {
				Debug::trace("[QUERY] $query");
				Debug::critical("[ERROR] ".
					"I'm sorry, Dave, I'm afraid I can't do that. ".
					mysql_error());
				error_log($query);
				error_log(mysql_error());
			}
			elseif ($log) {
				Debug::trace("[QUERY] $query");
			}
			
			return $res;
		}
		
		function queryRecord($query)
		{
			$res = Database::query($query);
			$rec = mysql_fetch_assoc($res);
			
			return $rec;
		}
		
		function queryValue($query, $col = 0)
		{
			$res = Database::query($query);
			$val = @mysql_result($res, $col);
			
			return $val;
		}
		
		function queryObject($query)
		{
			$res = Database::query($query);
			return mysql_fetch_object($res);
		}
		
		function queryIdList($query, $log = false)
		{
			$res = Database::db_query($query, $log);
			$err = mysql_errno();
			$arr = false;
			
			if ($res) {
				$arr = array();
				
				while ($rec = mysql_fetch_array($res)) {
					$arr[] = $rec[0];
				}
			}
			
			return $arr;
		}
		
		function getInsertId($result = 0)
		{
			if ($result != 0) {
				return mysql_insert_id($result);
			}
			else {
				return mysql_insert_id();
			}
		}
		
		function getRowCount($result)
		{
			return mysql_num_rows($result);
		}
		
		function jumpToRow($result, $rowNum)
		{
			return mysql_data_seek($result, $rowNum);
		}
		
		function fetchMap($result)
		{
			return mysql_fetch_assoc($result);
		}
		
		function fetchObject($result)
		{
			return mysql_fetch_object($result);
		}
		
		function close($conn = 0)
		{
			if ($conn != 0) {
				mysql_close($conn);
			}
			else {
				mysql_close();
			}
		}
	}
