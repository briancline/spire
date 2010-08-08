<?php

	class Validation
	{
		static function isEmailAddress($email) {
			return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
		}
	}
