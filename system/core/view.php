<?php

	class View
	{
		private $_root;
		private $_file;
		private $_variables = array();
		
		function __construct($file = '') {
			$this->_root = VIEW_ROOT;
			$this->_file = $file;
		}
		
		function assign($variable, $value) {
			$this->_variables[$variable] = $value;
		}
		
		function hasAssigned($variable) {
			return isset($this->_variables[$variable]);
		}
		
		function getValue($variable) {
			if ($this->hasAssigned($variable)) {
				return $this->_variables[$variable];
			}
			
			return NULL;
		}
		
		function viewFileExists($fileName)
		{
			if ($fileName[0] != '/') {
				$fileName = VIEW_ROOT .'/'. $fileName;
			}
			
			return file_exists($fileName);
		}
		
		function display($file = '') {
			if ($file) {
				$this->_file = $file;
			}
			
			if (!preg_match('/\.(php|html|tpl|tmpl)$/i', $this->_file)) {
				$this->_file .= '.php';
			}
			
			$filePath = $this->_root .'/'. $this->_file;
			
			if (file_exists($filePath)) {
				foreach ($this->_variables as $key => $value) {
					$$key = $value;
				}
				
				include($filePath);
			}
			else {
				Debug::critical('View %s does not exist.', $this->_file);
			}
		}
	}
