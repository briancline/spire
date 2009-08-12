<?php
	
	// Global config
	$global = array();
	
	// Site
	$global['url'] = 'http://www.mysite.com/';
	$global['url_chars'] = '/^[a-z0-9:.?_\/=-]+$/i';
	
	// Controller
	$global['default_controller'] = 'home';
	$global['default_method'] = 'index';
	
	// Memcache
	$global['memcache_enabled'] = false;
	$global['memcache_host'] = '127.0.0.1';
	$global['memcache_port'] = 11211;
	$global['memcache_timeout'] = 2;
	
	// Database config
	$global['db_host'] = '';
	$global['db_user'] = '';
	$global['db_pass'] = '';
	$global['db_database'] = '';
	
	// Model behavior
	$global['model_auto_discovery'] = true;
	
	// Library behavior
	$global['library_auto_discovery'] = true;
?>
