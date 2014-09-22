<?php
session_start();

include './functions/user.class.php';

$user = new User;

$test = array(
	'email' => 'testing@testing.com',
	'password' => 'Tom Tittlebrock'
	// 'phone' => '619-249-6831'
	// 'password' => 'Pa$$worD12'
);

// $user->createUser($test);
$user->loginUser($test);