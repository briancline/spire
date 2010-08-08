<?php

	class JsonResponse
	{
		var $error = false;
		var $message = '';
		
		function setError($message)
		{
			$this->error = true;
			$this->message = $message;
		}
	}
