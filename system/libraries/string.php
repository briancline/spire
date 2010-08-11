<?php

	class StringUtils
	{
		function urlEncode($str)
		{
			//$str = urlencode($str);
			$str = str_replace("\r", '%0D', $str);
			$str = str_replace("\n", '%0A', $str);
			$str = str_replace('"', '%22', $str);
			$str = str_replace("'", '%27', $str);
			return $str;
		}
	}
