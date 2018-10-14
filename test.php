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

require_once 'loader.php';

	$pdo = new PDO('mysql:dbname=dev_bot;host=localhost;charset=utf8', 'master', '12dsfgNJKGH&34');

App\Model::_setDB($pdo);
App\Model::setPrefix('app_');
$user = new Users(27);

echo $user->username;

var_dump($user->getContacts(array('order' => 'contact_id DESC')));



/** Create new User */
/*	$user = new Users;
	$user->email = "szpaaaaq@gmail.com";
	$user->password = md5('salt_string2357111317192329374357:szpaaaaq@gmail.com');
	$user->username = 'SzpaQ';
	$user->role_id = 1;
	$user->save();
*/
/** find One user */
//	Users::findFirst(array('user_id = :uid', 'bind'=>array('uid' => 1)));
	




