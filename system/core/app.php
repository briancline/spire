<?php

	class App
	{
		// Global and database config
		private $global_config;
		private $db_config;
		
		// Database connection
		private $db_conn;
		
		// Memcached connection
		private $memcache;
		
		// Request URI
		private $request;
		private $request_length;
		private $request_controller;
		private $request_method;
		private $request_arguments;
		
		// Query string
		private $query_string;
		
		function __construct()
		{
			$host = Config::get('db_host');
			$user = Config::get('db_user');
			$pass = Config::get('db_pass');
			$database = Config::get('db_database');
			
			$this->db_conn = Database::connect($host, $user, $pass, $database);
			
			if(Config::get('memcache_enabled')) {
				$this->memcache = new Memcache;
				$this->memcache->pconnect(
					Config::get('memcache_host'),
					Config::get('memcache_port'));
				Cache::$cache = $this->memcache;
				Cache::$prefix = Config::get('memcache_prefix');
			}
		}
		
		/**
		 * We're trying to clean the REQUEST_URI in order to deduce the
		 * controller to load, method of the controller to call, and what
		 * arguments to send that method.
		 *
		 * For example:
		 *  http://www.example.com/users/modify/83?profile=2
		 * 
		 * Where:
		 *  http://www.example.com/		-- root url as specified in $global['url']
		 *  users						-- is the controller we should instantiate and load
		 *  modify						-- method of the controller we should call
		 *  83							-- single argument to send to the modify method, but
		 *								   there could easily be more than one argument
		 *								   separated by additional /'s
		 *  ?							-- indicating the beginning of the query string
		 *								   and also the start of things we don't care about
		 *								   here
		 *
		 * Effectively:
		 *  $obj = new Users();
		 *  $obj->modify(83);
		 *
		 * Defaults:
		 *  If no controller is provided, we default to $global['default_controller'] and
		 *  if no method is provided, we default to $global['default_method']
		 */
		function prepare_request()
		{
			if(isset($_SERVER['PATH_INFO'])) {
				$this->request = $_SERVER['PATH_INFO'];
			}
			else {
				$this->request = $_SERVER['REQUEST_URI'];
			}
			
			if($this->request && !preg_match(Config::get('url_chars'), $this->request)) {
				return false;
			}
			
			// Remove the length of the query string off the end of the
			// request. +1 to the query string length to also remove the ?
			$this->query_string = $_SERVER['QUERY_STRING'];
			if(!empty($this->query_string) && false !== strpos($this->request, '?')) {
				$this->request = substr($this->request, 0, (strlen($this->query_string) + 1) * -1);
			}
			
			// Trash any leading slashes
			if($this->request[0] == '/') {
				$this->request = substr($this->request, 1);
			}
			
			// Reroute this URI if necessary
			$this->request = Routing::determineFinalRoute($this->request);
			
			$this->request = explode('/', $this->request);
			$this->request_length = count($this->request);
			
			// Trash the index.php match
			if($this->request[0] == 'index.php') {
				array_shift($this->request);
			}
			
			// Grab the controller, method and arguments
			$this->request_controller = array_shift($this->request);
			$this->request_method = array_shift($this->request);
			$this->request_arguments = $this->request;
			
			if(!$this->request_controller) {
				$this->request_controller = Config::get('default_controller');
			}
			
			if(!$this->request_method) {
				$this->request_method = Config::get('default_method');
			}
			
			return true;
		}
		
		/**
		 * Using the request_* private instance variables, we instantiate the appropriate
		 * class and call the appropriate method.
		 */
		function dispatch()
		{
			// Don't bother dispatching if we run from CLI
			if(empty($_SERVER['REQUEST_URI'])) {
				return;
			}
			
			// Prepare the request before attempting to dispatch.
			if(!$this->prepare_request()) {
				die("Could not properly prepare the request.");
			}
			
			$class = $this->request_controller;
			$method = $this->request_method;
			$class_file = CONTROLLER_ROOT.'/'.$class.'.php';
			
			if(!file_exists($class_file)) {
				header($_SERVER['SERVER_PROTOCOL'] .' 404 Not Found');
				die("Controller [$class] does not exist.");
			}
			
			// We've proven it exists and the user wants it, include
			// the controller class and instantiate it.
			include($class_file);
			$obj = new $class();
			
			/**
			 * If the method defines __call then clearly the implementer wants
			 * the ability to intercept and handle missing methods. Don't die
			 * if we can't find the method in a controller where __call is defined.
			 */
			if((!method_exists($obj, $method) && !method_exists($obj, '__call')) || !is_callable(array($obj, $method))) {
				header($_SERVER['SERVER_PROTOCOL'] .' 404 Not Found');
				die("Controller method [$class][$method] does not exist.");
			}
			
			call_user_func_array(array($obj, $method), $this->request_arguments);
		}
		
		function __destruct()
		{
			if($this->db_conn)
				Database::close($this->db_conn);
		}
	}
