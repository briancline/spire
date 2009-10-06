<?php

	class ArrayUtils
	{
		static public function fromSingleton($input)
		{
			if(!is_array($input)) {
				$input = array($input);
			}
			
			return $input;
		}
		
		static public function fromSingletonIfNotNull($input)
		{
			if($input) {
				$input = self::fromSingleton($input);
			}
			
			return $input;
		}
	}
