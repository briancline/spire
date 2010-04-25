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
	include(LIBRARY_ROOT.'/string.php');
	include(LIBRARY_ROOT.'/array.php');
	include(LIBRARY_ROOT.'/validation.php');
	
	// Include models as they are required
	function __autoload($class_name)
	{
		$class_name = strtolower($class_name);
		$model_path = MODEL_ROOT.'/'.$class_name.'.php';
		$lib_path = LIBRARY_ROOT.'/'.$class_name.'.php';
		
		if(Config::get('library_auto_discovery') && file_exists($lib_path))
			include($lib_path);
		elseif(Config::get('model_auto_discovery') && file_exists($model_path))
			include($model_path);
	}
	
	
	/**
	 * Bootstrap the App and dispatch the request.
	 */
	$APP = new App();
	$APP->dispatch();
	
?>
