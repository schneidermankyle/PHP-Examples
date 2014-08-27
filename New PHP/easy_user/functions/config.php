<?php

include './functions/user.class.php';

$user = new User;

$test = array(
	'email' => 'testing@testing.com',
	'name' => 'Tom Tittlebrock'
);

$user->createUser($test);