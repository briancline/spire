<?php

	require('../libraries/debug.php');
	require('../libraries/memcache.php');
	
	Debug::hide_level(DEBUG_TRACE);
	Memcache::$host = '10.0.0.5';

	$tmp_movie = '2001: A Space Odyssey';
	$iterations = 1000;
	$set_times = 0;
	$get_times = 0;
	
	for($i = 0; $i < $iterations; $i++)
	{
		$key = 'movie_'. $i;
		
		$ts_start = microtime(true);
		Memcache::set($key, $tmp_movie);
		$ts_end = microtime(true);
		$set_times += ($ts_end - $ts_start);
		
		$ts_start = microtime(true);
		$movie = Memcache::get($key);
		$ts_end = microtime(true);
		$get_times += ($ts_end - $ts_start);
	}
	
	$set_avg = $set_times / $iterations;
	$get_avg = $get_times / $iterations;
	
	Debug::infof('SET operations took an average of %0.5f seconds', $set_avg);
	Debug::infof('GET operations took an average of %0.5f seconds', $get_avg);
	Debug::infof('Movie name is %s', $movie);

	/**
	 * Desired output:
	 * 
	 * [INFO] SET operations took an average of 0.00016 seconds
	 * [INFO] GET operations took an average of 0.00022 seconds
	 * [INFO] Movie name is 2001: A Space Odyssey
	 *
	 */
	
?>
