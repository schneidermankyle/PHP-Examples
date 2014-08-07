<?php

session_name('admin');

session_start();

require "./functions/functions.php";

require "./functions/variables.php";

require "./functions/token.php";

// Before doing anything, we check to see if ip is banned from use.

$token = new token();

checkBan($ip, $conn);

if ( $_SERVER['REQUEST_METHOD'] === 'POST') {

	$username = escape($_POST['username']);

	$password = escape($_POST['password']);

	// do some quick regex

	if (!$username || !$password) {
		// may add preg match for secondary
		
		$status .= "<li>Please enter a valid username and password</li>";

	} else {

		$query = "SELECT * FROM users WHERE LOWER(username) = :username";
		$stmt = $conn->prepare($query);
		$stmt->bindValue(":username", "$username");
		$stmt->execute();

		if ($stmt->rowcount() == 1) {
			// Username found, lets see if there is a password
			$row = $stmt->fetch(PDO::FETCH_ASSOC);	
			require "./functions/blowfish.class.php";
			$bcrypt = new bcrypt(4);
			$hash = $bcrypt->hash($password);

			if ($bcrypt->verify($password, $row['password'])) {

				$sess_username = $row['username'];
				$sess_owner = $row['owner'];

				// Check against ip address

				$query = "SELECT * FROM ip_address WHERE ip = :address";
				$stmt = $conn->prepare($query);
				$stmt->bindValue(":address", $ip);
				$stmt->execute();

				if ($stmt->rowCount() == 1) {

					// We have found a white listed ip
					$row = $stmt->fetch(PDO::FETCH_ASSOC);

					if ($row['approved'] == 1 ) {
						// login user

						// Generate token
						$generate = $token->get_token($sess_username);
						
						// Store token in db and session
						$last_login = $conn->prepare("UPDATE users SET last_login = :last_login WHERE username = :username");
						$last_login->execute(array('last_login' => $timeStamp, 'username' => $sess_username));

						$set_key = $conn->prepare("INSERT INTO nonce(`token`, date_added, date_expires) VALUES (:token, :date_added, :date_expires)");
						$set_key->execute(array('token' => $generate, 'date_added' => $timeStamp, 'date_expires' => $user_expire));

						if ($last_login->rowCount() == 1 && $set_key->rowCount() == 1) {

							$_SESSION['tk'] = $generate;
							$_SESSION['username'] = $sess_username;
							$_SESSION['owner'] = $sess_owner;
							header('Location: ./index.php');
						
						}
					} else {
						// Ip has been found in database but is not approved yet.

						$status .= "<li>Account approvel is currently pending, please contact administrator and try again later.</li>";

					}

					// Check to make sure ip now match username ?
					
				} else {
					// no ip found.
					// Ip needs to be approved
					// Create a key
					$browser = md5($_SERVER['HTTP_USER_AGENT']);
					$request = $_SERVER['REQUEST_TIME_FLOAT'];
					$key = $ip . $browser . $request;
					$key =  hash('sha256', "$key"); 


					// Log attempt and Set key to db

					$put = $conn->prepare("INSERT INTO ip_address(`ip`, username, date_added, date_expires, `key`) VALUES (:ip, :username, :date_added, :date_expires, :key)");
					$put->execute(array('ip' => $ip, 'username' => $username, 'date_added' => $timeStamp, 'date_expires' => $expire, 'key' => $key));

					if ($put->rowCount() > 0) {
						
						mail("6192496831@messaging.sprintpcs.com", "", "An unknown ip is trying to connect as an Admin, please visit http://www.riivupro.com/admin/confirm.php?key=$key", "From: RiivuPro <admin@riivupro.com>\r\n");

					}

					
					$status .= "<li>You are logging in from an unknown ip, please wait while our system admin approves this login</li>";

					// login needs to be logged

				}

				// header('Location: ./index.php');
			
			} else {

				$status .= "<li>User/Password missmatch</li>";
				// log failed attempt file or db? not sure yet.

			}

		}	else {

			$status .= "<li>There is issues with your username please contact a system administrator. (Error code: x100003)</li>";
		
		}


	}

		echo "$status";

}

?>