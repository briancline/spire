<?php

	class Config
	{
		static function get($key)
		{
			global $global;
			
			if(!array_key_exists($key, $global))
				return false;
			
			return $global[$key];
		}
	}

?>