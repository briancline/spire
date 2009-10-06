<?php

	class User extends Model
	{
		var $_table_name = 'users';
		var $_key_field = 'user_id';
		var $_insert_timestamp_field = 'create_date';
		var $_update_timestamp_field = 'update_date';
		
		static function find($criteria, $sort = false, $limitStart = false, $limitEnd = false) {
			return parent::find('User', 'users', $criteria, $sort, $limitStart, $limitEnd);
		}
		static function findByUserId($userId) {
			return self::find(array('user_id' => $userId));
		}
	}