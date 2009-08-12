<?php

	class Controller
	{
		function __construct()
		{
		}
		
		function posted_data()
		{
			return ($_POST && is_array($_POST) && count($_POST) > 0);
		}
	}

?>