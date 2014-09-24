<?php
session_start();

include './functions/user.class.php';

$user = new User;

$test = array(
	'email' => 'test@testing.com',
	'name' => 'Tom Tittlebrock',
	'password' => 'YWGTg4sdf2',
	'phone' => '844-333-8123'
	// 'password' => 'Pa$$worD12'
);

$update = array(
	'email' => 'test@test.com',
	'name' => 'Sassy Powdersalt',
	'password' => 'PA$$word$$12',
	'phone' => '555-620-9091'
);

// $user->createUser($test);
$user->loginUser($test);


// if ($user->verifyUser()) {
// 	// var_dump ( $user->retreiveUserInfo('username, email, phone, name, status') );
// 	// $user->updateUserInfo($test);
// }

// $user->logoutUser();
// $user->resetPassword($test['email']);
