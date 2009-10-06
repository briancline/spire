<?php

	class Routing
	{
		static function determineFinalRoute($route)
		{
			global $routes;
			
			foreach($routes as $inPattern => $outPattern)
			{
				$inPattern = str_replace(':any', '.+', $inPattern);
				$inPattern = str_replace(':num', '[0-9]+', $inPattern);
				$inPattern = '@^'. $inPattern .'$@';
				$outPattern = str_replace('$', '\\', $outPattern);
				
				if(preg_match($inPattern, $route))
				{
					$route = preg_replace($inPattern, $outPattern, $route);
					break;
				}
			}
			
			return $route;
		}
	}

?>