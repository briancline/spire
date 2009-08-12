<?php

	class Memcache
	{
		static $host = '127.0.0.1';
		static $port = 11211;
		static $timeout = 2;
		
		function get($key)
		{
			Debug::tracef('Getting value for key %s', $key);
			$data = '';
			
			$fd = fsockopen(Memcache::$host, Memcache::$port, $errno, $errstr, Memcache::$timeout * 1000);
			if(!$fd)
			{
				Debug::criticalf('Cannot connect to memcache: %s', $errstr);
				return false;
			}
			
			fputs($fd, "get $key\r\n");
			
			while(true)
			{
				$buffer = fgets($fd);
				
				if($buffer == "END\r\n")
				{
					Debug::trace('Received END notice');
					break;
				}
				elseif(eregi("^VALUE ([^ ]+) ([0-9]+) ([0-9]+)\r\n$", $buffer, $matches))
				{
					Debug::tracef('Received response "%s"', $buffer);
					
					$data = '';
					$datalen = $matches[3];
					$remaining_bytes = $datalen + 2; // Add 2 for \r\n
					
					Debug::tracef('Expecting value length %d (%d from socket)', $datalen, $remaining_bytes);
					
					while($remaining_bytes > 0)
					{
						$tmpbuf = fread($fd, $remaining_bytes);
						$tmplen = strlen($tmpbuf);
						
						Debug::tracef('Socket read [%d/%d] "%s"', $tmplen, $remaining_bytes, $tmpbuf);
						
						if($tmplen == 0)
							break;
						
						$remaining_bytes -= $tmplen;
						$data .= $tmpbuf;
					}
					
					// Truncate off the trailing \r\n since it's not part of the original data
					$data = substr($data, 0, $datalen);
					
					Debug::tracef('Received all data, length was %d', strlen($data));
					break;
				}
			}
			
			fclose($fd);
			
			return $data;
		}
		
		
		function set($key, $value)
		{
			Debug::tracef('Setting value of %s to %s', $key, $value);
			
			$len = strlen($value);
			$expire = 60;
			$flags = 0;
			
			$fd = fsockopen(Memcache::$host, Memcache::$port, $errno, $errstr, Memcache::$timeout * 1000);
			if(!$fd)
			{
				Debug::criticalf('Cannot connect to memcache: %s', $errstr);
				return false;
			}
			
			fputs($fd, "set $key $flags $expire $len\r\n$value\r\n");
			
			$buffer = fgets($fd);
			if($buffer == "STORED\r\n")
			{
				Debug::tracef('Successfully stored key %s with value "%s"', $key, $value);
				fclose($fd);
				return true;
			}
			
			Debug::tracef('Received odd response "%s"', $buffer);
			return false;
		}
	}

?>