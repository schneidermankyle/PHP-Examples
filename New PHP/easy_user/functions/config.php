<?php

include './functions/user.class.php';

$user = new User;

$test = array(
	'email' => 'test@testing.com',
	'name' => 'tom'
);

$user->createUser($test);