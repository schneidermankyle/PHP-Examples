<?php

require './functions/config.php';

function connect($config, $db='studiobl_search')
{
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

function query($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	$stmt->execute($bindings);


	$results = $stmt->fetchAll();

	return $results ? $results : false;
}

function get($tableName, $conn, $part)
{
	try {
		$result = $conn->query("SELECT * FROM $tableName WHERE partnumber LIKE '%$part%' OR name LIKE '%$part%'");

		return ( $result->rowCount() > 0 )
			? $result
			: false;
	} catch(PDOException $e) {
		echo 'ERROR: ' . $e->getMessage();
		return false;
	}
}

function put($query, $bindings, $conn)
{
	$stmt = $conn->prepare($query);
	return $stmt->execute($bindings);
}

function truncate($input, $limit, $break=".", $pad="...")
{
  if(strlen($input) <= $limit) return $input;

  if(false !== ($breakpoint = strpos($input, $break, $limit))) {
    if($breakpoint < strlen($input) - 1) {
      $input = substr($input, 0, $breakpoint) . $pad;
    }
  }

  return $input;
}

function checkBan($ip, $conn)
{

  $check = "SELECT * FROM black_list WHERE `ip` = :ip";
  $statement = $conn->prepare($check);
  $statement->bindValue('ip', $ip);
  $statement->execute();

  if ($statement->rowCount() > 0) {
    die('This ip has been banned');
  }

}

function calculateDate($date="", $amount="PT0H") {
  $date = new DateTime($date);

  $expiration = $date->add(new DateInterval($amount));
  $timeStamp = $date->format('d F Y H:i:s');

  return $timeStamp;
}

function escape($input) {

  $input = strip_tags($input);
  $input = mysql_real_escape_string($input);

  return $input;

}

function input_check($input) {
  $error = 0;
  $whitelist = '/^[a-zA-Z0-9\s,\.\+\(\)!_]+$/';

  foreach ($input as $row) {

    if ($row) {

      $check = preg_match($whitelist, $row);

      if ($check == FALSE) {
        // we have an error
        $error = 1;

      }
    }
  }

  return $error;
}