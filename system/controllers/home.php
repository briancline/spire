<?php

	class Home extends AppController
	{
		public function index()
		{
			$v = new View('home.index.php');
			$v->display();
		}

	}


