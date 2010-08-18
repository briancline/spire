<?php
	
	/**
	 * E:
	 * Return a string with all special HTML entities safely encoded.
	 */
	function E($string) {
		return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
	}
	
	/**
	 * EN:
	 * Return a string in the same fashion as E() but with line breaks
	 * represented as <br /> tags.
	 */
	function EN($string) {
		return nl2br(E($string));
	}
	
	
	class StringUtils
	{
		/**
		 * urlEncode: returns a URL-encoded representation of the input string.
		 * PHP's built-in urlencode() was not entirely suitable for some 
		 * reason...so add things here as needed.
		 *
		 * TODO: This is odd and seemingly app-specific. Rethink and rework.
		 */
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
