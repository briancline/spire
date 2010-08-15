<?php

	class Routing
	{
		/**
		 * determineFinalRoute:
		 * Inspects the provided URI string to determine if any routes
		 * specified in SYS/config/routing.php match. If so, rewrite them
		 * as necessary. Returns the final rewritten route, or the same
		 * URI if it was not rewritten.
		 */
		static function determineFinalRoute($route)
		{
			global $routes;
			
			foreach ($routes as $inPattern => $outPattern) {
				$inPattern = str_replace(':any', '.+', $inPattern);
				$inPattern = str_replace(':num', '[0-9]+', $inPattern);
				$inPattern = '@^'. $inPattern .'$@';
				$outPattern = str_replace('$', '\\', $outPattern);
				
				if (preg_match($inPattern, $route)) {
					$route = preg_replace($inPattern, $outPattern, $route);
					break;
				}
			}
			
			return $route;
		}
	}
