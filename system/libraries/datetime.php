<?php

	class DateTimeUtils
	{
		/**
		 * getRelativeTime: Accepts a epoch timestamp or a written date/time
		 * and returns a human-readable string indicating the relative date
		 * and time of the input.
		 * 
		 * Adapted from a comment from 'yasmary' at http://php.net/time,
		 * who based it on an earlier comment from 'macrobert'.
		 */
		function getRelativeTime($date)
		{
			if (empty($date)) {
				return 'No date provided';
			}
			elseif (is_numeric($date)) {
				$date = date('r', $date);
			}

			$periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
			$lengths = array('60',     '60',     '24',   '7',   '4.35', '12',    '10');

			$now = time();
			$unixDate = strtotime($date);

			if (empty($unixDate)) {    
				return 'Bad date';
			}

			if ($now > $unixDate) {
				$difference = $now - $unixDate;
				$tense = 'ago';
			}
			else {
				$difference = $unixDate - $now;
				$tense = 'from now';
			}

			for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
				$difference /= $lengths[$j];
			}

			$difference = round($difference);

			if ($difference != 1) {
				$periods[$j] .= 's';
			}

			return "{$difference} {$periods[$j]} {$tense}";
		}
	}
