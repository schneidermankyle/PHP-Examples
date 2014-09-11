<?php

include './functions/user.class.php';

$user = new User;

$test = array(
	'email' => 'testing@testing.com',
	'name' => 'Tom Tittlebrock',
	'phone' => '619-249-6831'
);

$user->createUser($test);