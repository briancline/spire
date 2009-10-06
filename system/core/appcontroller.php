<?php

	class AppController extends Controller
	{
		protected $postVars = false;
		
		
		function __construct()
		{
			parent::__construct();
			
			$post = new stdClass();
			foreach($_POST as $postKey => $postValue) {
				$this->postVars->$postKey = $postValue;
			}
		}
		
		function __destruct()
		{
			parent::__destruct();
		}
	}
	
?>