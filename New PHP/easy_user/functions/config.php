<?php

include './functions/user.class.php';

$db_info = array(
	'DB_USERNAME' => 'root',
	'DB_PASSWORD' => 'root'
);

function connect($config, $db='user_system') {
	try {
		$conn = new PDO("mysql:host=localhost;dbname=$db",
		$config['DB_USERNAME'],
		$config['DB_PASSWORD']);

		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $conn;

	} catch(Exception $e) {

		return false;

	}
}

$conn = connect($db_info);
$user = new User;