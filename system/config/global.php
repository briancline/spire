<?php
	
	// Global config
	$global = array();
	
	// Site
	$global['domain_name'] = 'domain.com';
	$global['url'] = 'http://domain.com/';
	$global['url_chars'] = '/^[a-z0-9:.?_\/=-]+$/i';
	
	// Controller
	$global['default_controller'] = 'home';
	$global['default_method'] = 'index';
	
	// Memcache
	$global['memcache_enabled'] = false;
	$global['memcache_host'] = '127.0.0.1';
	$global['memcache_port'] = 11211;
	$global['memcache_timeout'] = 2;
	$global['memcache_prefix'] = 'mysite:';
	
	// Database config
	$global['db_host'] = 'localhost';
	$global['db_user'] = 'domain_live';
	$global['db_pass'] = '';
	$global['db_database'] = 'domain_live';
	
	// Library behavior
	$global['library_auto_discovery'] = true;

	// Model behavior
	$global['model_auto_discovery'] = true;
	$global['model_table_discovery'] = true;
	
	// Detect if we're on the dev site
	$global['is_dev_site'] = preg_match('#/sub/dev/#', dirname(__FILE__));
	
	// Set appropriate variables for test environment
	if($global['is_dev_site']) {
		$global['domain_name'] = 'dev.domain.com';
		$global['url'] = 'http://dev.domain.com/';
		
		$global['db_user'] = 'domain_dev';
		$global['db_pass'] = '';
		$global['db_database'] = 'domain_dev';
	}
