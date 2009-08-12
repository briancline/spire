<?php
	
	define('DEBUG_TRACE',    0x01);
	define('DEBUG_INFO',     0x02);
	define('DEBUG_WARN',     0x04);
	define('DEBUG_CRITICAL', 0x08);

	
	class Debug
	{
		static $log_level = 0x0f; // Log everything by default
		
		
		function __construct()
		{
			die('The Debug class cannot be instantiated.');
		}
		
		
		function set_level($level)
		{
			Debug::$log_level = $level;
		}
		
		
		function hide_level($level)
		{
			if(Debug::is_logging($level))
				Debug::$log_level &= ~$level;
		}
		
		
		function is_logging($level)
		{
			return (Debug::$log_level & $level) == $level;
		}
		
		
		static function critical($format)
		{
			if(!Debug::is_logging(DEBUG_CRITICAL))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::log_to_screen('CRIT', $message);
		}
		
		
		static function warn($format)
		{
			if(!Debug::is_logging(DEBUG_WARN))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::log_to_screen('WARN', $message);
		}
		
		
		static function info($format)
		{
			if(!Debug::is_logging(DEBUG_INFO))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::log_to_screen('INFO', $message);
		}
		
		
		static function trace($format)
		{
			if(!Debug::is_logging(DEBUG_TRACE))
				return false;
			
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::log_to_screen('TRACE', $message);
		}
		
		
		static function log_to_screen($level_name, $message)
		{
			$is_cli = (php_sapi_name() == 'cli');
			$class_name = strtolower($level_name);
			
			if($is_cli)
			{
				printf("[%s] %s\n", $level_name, $message);
			}
			else
			{
				printf('<div class="debug debug_%s">[%s] %s</div>%s', $class_name, $level_name, $message, "\n");
			}
		}
	}

?>