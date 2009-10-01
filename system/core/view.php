<?php

	class View
	{
		private $_root;
		private $_file;
		private $_variables = array();
		
		function __construct($file = "")
		{
			$this->_root = VIEW_ROOT;
			$this->_file = $file;
		}
		
		function assign($variable, $value)
		{
			$this->_variables[$variable] = $value;
		}
		
		function display($file = "")
		{
			if($file) {
				$this->_file = $file;
			}
			
			if(!preg_match('/\.(php|html|tpl|tmpl)$/i', $this->_file)) {
				$this->_file .= '.php';
			}
			
			$file_path = $this->_root .'/'. $this->_file;
			
			if(file_exists($file_path))
			{
				foreach($this->_variables as $key => $value) {
					$$key = $value;
				}
				
				include($file_path);
			}
			else
			{
				Debug::critical('View %s does not exist.', $this->_file);
			}
		}
	}

?>