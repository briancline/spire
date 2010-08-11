<?php
	
	define('DEBUG_TRACE',    0x01);
	define('DEBUG_INFO',     0x02);
	define('DEBUG_WARN',     0x04);
	define('DEBUG_CRITICAL', 0x08);

	
	class Debug
	{
		static $logLevel = 0x0f; // Log everything by default
		
		
		function __construct()
		{
			die('The Debug class cannot be instantiated.');
		}
		
		
		function setLevel($level)
		{
			Debug::$logLevel = $level;
		}
		
		
		function hideLevel($level)
		{
			if (Debug::isLogging($level))
				Debug::$logLevel &= ~$level;
		}
		
		
		function isLogging($level)
		{
			return (Debug::$logLevel & $level) == $level;
		}
		
		
		static function critical($format)
		{
			if (!Debug::isLogging(DEBUG_CRITICAL))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::logToScreen('CRIT', $message);
		}
		
		
		static function warn($format)
		{
			if (!Debug::isLogging(DEBUG_WARN))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::logToScreen('WARN', $message);
		}
		
		
		static function info($format)
		{
			if (!Debug::isLogging(DEBUG_INFO))
				return false;
				
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::logToScreen('INFO', $message);
		}
		
		
		static function trace($format)
		{
			if (!Debug::isLogging(DEBUG_TRACE))
				return false;
			
			$arguments = func_get_args();
			$format = array_shift($arguments);
			
			$message = vsprintf($format, $arguments);
			
			Debug::logToScreen('TRACE', $message);
		}
		
		
		static function logToScreen($levelName, $message)
		{
			$isCli = (php_sapi_name() == 'cli');
			$className = strtolower($levelName);
			
			if ($isCli) {
				printf("[%s] %s\n", $levelName, $message);
			}
			else {
				printf('<div class="debug debug_%s">[%s] %s</div>%s', $className, $levelName, $message, "\n");
			}
		}
	}
