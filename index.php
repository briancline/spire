<?php

	define('START_TIME', microtime(true));
	error_reporting(E_ALL);
	
	define('ROOT', dirname(__FILE__));

	// System paths
	define('CONFIG_ROOT', ROOT.'/system/config');
	define('CORE_ROOT', ROOT.'/system/core');
	define('LIBRARY_ROOT', ROOT.'/system/libraries');
	
	// Application paths
	define('CONTROLLER_ROOT', ROOT.'/system/controllers');
	define('MODEL_ROOT', ROOT.'/system/models');
	define('VIEW_ROOT', ROOT.'/system/views');
	
	// Include global and database configuration
	include(CONFIG_ROOT.'/global.php');
	include(CONFIG_ROOT.'/routing.php');
	
	// Include core classes
	include(CORE_ROOT.'/app.php');
	include(CORE_ROOT.'/controller.php');
	include(CORE_ROOT.'/library.php');
	include(CORE_ROOT.'/model.php');
	include(CORE_ROOT.'/view.php');
	include(CORE_ROOT.'/jsonresponse.php');
	include(CORE_ROOT.'/appcontroller.php');
	
	// Include libraries
	include(LIBRARY_ROOT.'/config.php');
	include(LIBRARY_ROOT.'/routing.php');
	include(LIBRARY_ROOT.'/debug.php');
	include(LIBRARY_ROOT.'/datetime.php');
	include(LIBRARY_ROOT.'/database.php');
	include(LIBRARY_ROOT.'/cache.php');
	include(LIBRARY_ROOT.'/string.php');
	include(LIBRARY_ROOT.'/array.php');
	include(LIBRARY_ROOT.'/validation.php');
	
	// Include models as they are required
	function __autoload($className)
	{
		$className = strtolower($className);
		$modelPath = MODEL_ROOT.'/'.$className.'.php';
		$libPath = LIBRARY_ROOT.'/'.$className.'.php';
		
		if (Config::get('library_auto_discovery') && file_exists($libPath)) {
			include($libPath);
		}
		elseif (Config::get('model_auto_discovery') && file_exists($modelPath)) {
			include($modelPath);
		}
	}
	
	
	/**
	 * Bootstrap the App and dispatch the request.
	 */
	$APP = new App();
	$APP->dispatch();
