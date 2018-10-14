<?php

/**
 * 
 * LICENCE
 * ALL RIGHTS RESERVED.
 * YOU ARE NOT ALLOWED TO COPY/EDIT/SHARE/WHATEVER.
 * IN CASE OF ANY PROBLEM CONTACT AUTHOR.
 * @author    Łukasz Szpak (szpaaaaq@gmail.com)
 * @Copyright 2018 SzpaQ
 * @license   ALL RIGHTS RESERVED
 *
 * * */
class Users extends App\Model {
	
	public function initialize() {
		$this->hasMany('user_id', 'Contacts');
	}
	public static function getSource() {
		return 'app_chat_users';
	}
	
}
