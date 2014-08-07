<?php
class token {
	public function get_token($username) {
		// Create another token
		$browser = md5($_SERVER['HTTP_USER_AGENT']);
		$string = $username . $_SERVER['REMOTE_ADDR'] . $browser . microtime(TRUE);
		return hash('sha256', $string);
	}

	public function verify_token($conn) {
		// Query database and returning
		$query = "SELECT * FROM nonce WHERE `token` = :token";
		$stmt = $conn->prepare($query);
		$stmt->bindValue(':token', $_SESSION['tk']);
		$stmt->execute();

		if ($stmt->rowCount() > 0 ) {
			// Do our ip and approve test

			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (strtotime('now') < strtotime($row['date_expires']) ) {
				// Token is good 
				
				return 1;
			} else {
				session_unset();
				session_destroy();
				header("Location: ./login.php?error='2'");

			}


		} else {

			header("Location: ./login.php?error='1'");
		
		}
	}

	public function get_antic($username, $formname) {
		// Create a token for each page reload
		$browser = md5($_SERVER['HTTP_USER_AGENT']);
		$part1 = md5($username . $_SERVER['REMOTE_ADDR']);
		$part2 = md5(microtime(TRUE) * mt_rand());
		$string = $part1 . $part2 . $formname;
		$hash = hash('sha256', $string);

		$_SESSION['antic'] = $hash;

		return $hash;
	}

	public function verify_antic($token) {
		// Check to make sure form submit matches token
		$ret = 0;

		if ($token == $_SESSION['antic'] && strlen($_POST['type1']) == 64) {
			$ret = 1;
		}

		return $ret;
	}

}