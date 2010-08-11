<?php

	class DateTimeUtils
	{
		function getRelativeTime($date)
		{
		    if (empty($date)) {
		        return "No date provided";
		    }
		    elseif (is_numeric($date))
		    {
		    	$date = date('r', $date);
		    }
		    
		    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
		    $lengths         = array("60","60","24","7","4.35","12","10");
		    
		    $now             = time();
		    $unixDate        = strtotime($date);
		    
		       // check validity of date
		    if (empty($unixDate)) {    
		        return "Bad date";
		    }
		
		    // is it future date or past date
		    if ($now > $unixDate) {    
		        $difference     = $now - $unixDate;
		        $tense         = "ago";
		        
		    } else {
		        $difference     = $unixDate - $now;
		        $tense         = "from now";
		    }
		    
		    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		        $difference /= $lengths[$j];
		    }
		    
		    $difference = round($difference);
		    
		    if ($difference != 1) {
		        $periods[$j].= "s";
		    }
		    
		    return "$difference $periods[$j] {$tense}";
		}
	}
