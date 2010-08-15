<?php

	class Controller
	{
		protected $postVars = null;
		protected $getVars = null;

		function __construct()
		{
			$this->postVars = new stdClass();
			$this->getVars = new stdClass();

			foreach ($_POST as $postKey => $postValue) {
				$this->postVars->$postKey = $postValue;
			}
			foreach ($_GET as $getKey => $getValue) {
				$this->getVars->$getKey = $getValue;
			}
		}
		
		function __destruct()
		{
		}
		
		protected function postedData()
		{
			return (isset($_POST) && is_array($_POST) && count($_POST) > 0);
		}

		protected function submittedData()
		{
			return (isset($_GET) && is_array($_GET) && count($_GET) > 0);
		}
		
		protected function redirect($url)
		{
			header('Location: '. $url);
			exit;
		}
		
		protected function redirectToReferrer()
		{
			return $this->redirect($_SERVER['HTTP_REFERER']);
		}
		
		protected function getSecureSiteUri($path = '', $subdomain = '')
		{
			return $this->getSiteUri($path, $subdomain, true);
		}
		
		protected function getSiteUri($path = '', $subdomain = '', $isSecure = false)
		{
			if ($subdomain != '') {
				$uri = 'http://'. $subdomain .'.'. Config::get('domain_name');
			}
			else {
				$uri = Config::get('url');
			}

			if ($isSecure) {
				$uri = preg_replace('/^http:/https:/i', $uri);
			}
			
			// Avoid double-slashes between the root URI and the path.
			$uriLength = strlen($uri);
			if ($uri[$uriLength - 1] == '/' && strlen($path) > 0 && $path[0] == '/') {
				$path = substr($path, 1);
			}
			
			return $uri . $path;
		}
		
		
		
		/********************************************************************************
		 *** POST-BASED JSON METHODS ****************************************************
		 ********************************************************************************/
		protected function getJsonPostRequest($reqFieldName = 'request')
		{
			$obj = json_decode($_POST[$reqFieldName]);
			
			if (!is_object($obj)) {
				return false;
			}
			
			foreach (get_object_vars($obj) as $name => $value) {
				if (is_string($value)) {
					$value = trim($value);
				}
				
				$obj->$name = $value;
			}
			
			return $obj;
		}
		
		protected function sendJsonRequest($url, $req = '', $reqFieldName = 'request')
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			if ($req != '') {
				$req = json_encode($req);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $reqFieldName .'='. urlencode($req));
			}
			
			$resp = curl_exec($ch);
			
			return json_decode($resp);
		}
		
		protected function sendJsonResponse($obj)
		{
			header('Content-type: application/x-javascript');
			echo(json_encode($obj));
		}
		
		protected function sendJsonError($message)
		{
			$resp = new JsonResponse();
			$resp->setError($message);
			
			return $this->sendJsonResponse($resp);
		}

	}
