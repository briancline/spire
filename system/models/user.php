<?php

	class User extends Model
	{
		protected static $_table_name = 'users';
		protected static $_key_field = 'user_id';
		protected static $_insert_timestamp_field = 'create_date';
		protected static $_update_timestamp_field = 'update_date';
		protected static $_cachePrefix = 'user:';
		
		static function findByEmail($email, $sort = false, $limitStart = false, $limitEnd = false) {
			return self::find(array('email' => $userId), $sort, $limitStart, $limitEnd);
		}
	}