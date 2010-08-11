<?php

	/**
	 * Cache library
	 * 
	 * Requirements: PHP 5.3+
	 *               Memcache PHP module (available via PECL)
	 */
	
	class Cache
	{
		public static $cache = null;
		public static $prefix = '';
		
		public static function get($key) {
			if (!self::$cache) {
				return false;
			}

			//Debug::info("Getting key %s", self::$prefix . $key);
			return self::$cache->get(self::$prefix . $key);
		}
		
		public static function dirty($key) {
			if (!self::$cache) {
				return false;
			}

			//Debug::info("Dirtying key %s", self::$prefix . $key);
			return self::$cache->delete(self::$prefix . $key);
		}
		
		public static function set($key, $value, $expiration = 0) {
			if (!self::$cache) {
				return false;
			}

			//Debug::info("Setting key %s", self::$prefix . $key);
			return self::$cache->set(self::$prefix . $key, $value, 0, $expiration);
		}
		public static function setCompressed($key, $value, $expiration = 0) {
			if (!self::$cache) {
				return false;
			}

			//Debug::info("Setting compressed key %s", self::$prefix . $key);
			return self::$cache->set(self::$prefix . $key, $value, MEMCACHE_COMPRESSED, $expiration);
		}
		
	}
