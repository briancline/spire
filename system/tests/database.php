<?php

	require('../core/model.php');

	require('../libraries/debug.php');
	require('../libraries/config.php');
	require('../libraries/memcache.php');
	require('../libraries/database.php');
	require('../config/global.php');

	require('../models/user.php');
	
	//Debug::hide_level(DEBUG_TRACE);
	
	Memcache::$host = Config::get('memcache_host');
	Database::connect(
		Config::get('db_host'),
		Config::get('db_user'),
		Config::get('db_pass'),
		Config::get('db_database')
	);
	
	Database::query("delete from users");
	
	$u = new User();
	$u->email_address = 'brian@systempoint.us';
	$u->password = md5('doh');
	$u->save();
	
	$new_id = $u->get_key_value();
	$u2 = new User($new_id);
	print_r($u2);
