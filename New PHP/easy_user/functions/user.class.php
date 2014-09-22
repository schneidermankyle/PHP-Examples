<?php
	/*
		Copyright (c) 2014 Kyle Schneiderman, http://kyleschneiderman.com/examples/easy_user/

		Permission is hereby granted, free of charge, to any person obtaining
		a copy of this software and associated documentation files (the
		"Software"), to deal in the Software without restriction, including
		without limitation the rights to use, copy, modify, merge, publish,
		distribute, sublicense, and/or sell copies of the Software, and to
		permit persons to whom the Software is furnished to do so, subject to
		the following conditions:

		The above copyright notice and this permission notice shall be
		included in all copies or substantial portions of the Software.

		THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
		EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
		MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
		NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
		LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
		OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
		WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	*/

class User {

	// Config array
	private $config = array(
		'db' => array(
			// Don't use root!
			'username' => 'root',
			// And come up with a better password!
			'password' => 'root',
			'host' => 'localhost',
			'db_name' => 'user_system'
		),
		'user' => array(
			'default_login_attempts' => 5
		)
	);

	// Track all of our errors
	private $error = array(
		// User errors
		1 => array(
			'101' => array(
				'error' => 'There was an error validating user information',
				'trigger' => ''
			),
			'102' => array(
				'error' => 'User is already registered',
				'trigger' => ''
			),
			'103' => array(
				'error' => 'Unexpected form data',
				'trigger' => ''
			),
			'104' => array(
				'error' => 'Erorr user/password mismatch',
				'trigger' => ''
			),
			'105' => array(
				'error' => 'Error, please wait a few seconds before trying to login again',
				'trigger' => ''
			),
			'106' => array(
				'error' => 'Error, your session has timed out',
				'trigger' => ''
			)
		),
		// Database Errors
		2 => array(
			'error' => 'There was an error connecting to the database',
			'trigger' => ''
		)
	);

	// Objects within the class
	public $conn;

	// Create the DB
	private function createDb($config) {
		try {
			// Create a temporary connection to create database schema
			$temp = new PDO("mysql:host=" . $this->config['db']['host'] . ";",
			$this->config['db']['username'],
			$this->config['db']['password']);
		
			$temp->exec('CREATE DATABASE `' . $this->config['db']['db_name'] . '`; 
				CREATE TABLE IF NOT EXISTS `' . $this->config['db']['db_name'] . '`.`Users` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`username` VARCHAR(45) NOT NULL,
				`password` VARCHAR(256) NOT NULL,
				`salt` VARCHAR(256) NOT NULL,
				`email` VARCHAR(254) NULL,
				`phone` VARCHAR(15) NULL,
				`name` VARCHAR(25) NOT NULL,
				`last_login_attempt` INT(20) NOT NULL DEFAULT ' . 0 . ',
				`failed_attempts` INT(1) NOT NULL DEFAULT ' . 0 . ',
				`token` VARCHAR(256) NULL,
				`timeout` INT(20) NULL,
				PRIMARY KEY (`id`));'
			);

			// Release the temp PDO for garbage collection
			unset($temp);

			// Re-run the construct method to ensure our connection object is set and pointing to proper PDO
			$this->constructDb();

		} catch (Exception $e) {

			$this->error[2]['trigger'] = $e;
			die($this->error[2]['trigger']);

		}
	}

	// This method needs to evolve a bit. revisiting this soon.
	private function constructDb() {
		try {
			// Set up the PDO using our config array
			$conn = new PDO("mysql:host=" . $this->config['db']['host'] . ";dbname=" . $this->config['db']['db_name'],
			$this->config['db']['username'],
			$this->config['db']['password']);

			// Set our error modes
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			// Return object if connection is true
			return $conn;

		} catch(Exception $e) {

			if ($e->getCode() == '1049') {
				// Failed due to database not existing, let's fix that.
				// Create and connect here
				$this->createDb();

			}

			return FALSE;
		}
	}

	// construct sets up everything we need, such as the db config
	public function __construct() {
		$this->conn = $this->constructDb();
		
	}

	// Verify data is legitimate
	private function validateData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				if ($value == FALSE) {
					$this->error[1]['101']['trigger'] = $key;
					return FALSE;
				}
			}

			return TRUE;
		}
	}

	// Sanitize user input and prep it for work with DB
	private function sanitizeInput($input) {
		// Make sure the input is coming in expected format
		if (is_array($input)) {
			foreach($input as $key => $field) {
				// Figure out what we are testing.
				switch ($key) {
					case 'email':
						// Sanitize the field
						$email = filter_var(strip_tags($field), FILTER_SANITIZE_EMAIL);
						// Do some more 
						$input['email'] = (filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : FALSE;						
						break;
					case 'username':
						// Change this if you would like non email login username
						$username = filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
						// Do some more 
						$input['username'] = (filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH)) ? $username : FALSE;						
						break;
					case 'name':
						$name = filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
						// Do some more
						$input['name'] = (preg_match("/^(([A-za-z]+[\s]{1}[A-za-z]+)|([A-Za-z]+))$/m", $name)) ? $name : FALSE;
						break;
					case 'phone':
						$phone = filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
						// Do some regex
						$input['phone'] = (preg_match("/^\s*(?:\+?(\d{1,3}))?([-. (]*(\d{3})[-. )]*)?((\d{3})[-. ]*(\d{2,4})(?:[-.x ]*(\d+))?)\s*$/m", $phone)) ? $phone : FALSE;
						break;
					case 'password':
						// We really don't want to limit passwords, more just ensure they aren't passing anything crazy in
						$password = filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

						$input['password'] = $password;
						break;
					default:
						// Decide what to do with the array.
						var_dump($this->error[1]['103']['error']);
				}
			}

			return $input;
		}
	}

	// Check to see if user is already in the system
	private function checkUser($user) {
		// Query the database to see if a result is returned
		$stmt = $this->conn->prepare("SELECT * FROM `users` WHERE `email` = :email");
		$stmt->bindValue(':email', $user['email']);
		$stmt->execute();

		if ($stmt->rowCount() > 0) {
			// Since there is a user, go ahead and return data
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} else {
			return FALSE;
		}
	}

	private function encryptPass($password, $salt = '') {
		// Encrypt password //

		// Generate CSPRNG salt
		$salt = (!$salt) ? base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM)) : $salt;

		// Convert strings to arrays for processing
		// Figure out which is shorter PW or SALT
		if (strlen($salt) >= strlen($password)) {
			$left = str_split($salt);
			$right = str_split($password);
		} else {
			$left = str_split($password);
			$right = str_split($salt);
		}
		
		$i = 0;
		$const = count($left);

		// Loop through the smaller string 
		foreach($left as $char) {
			// For each character, run the algorithm and figure out where to insert current char in the larger string
			$number = abs(floor((ord($char) - ord($right[$i])) / $const));
			$number = ($number > 0) ? ($number - 1) : $number;
			
			array_splice($left, $number, 0, $right[$i]);
			$i++;
		}

		// Hash it out		
		$prehash = implode('', $left);
		$hash = hash_pbkdf2("whirlpool", $prehash, $salt, 1000, 0);

		// return hash + salt
		return array(
			'pass' => $hash,
			'salt' => $salt
		);

	}

	private function registerUser($user) {
		if (is_array($user)) {
			// Update database

			$register = $this->conn->prepare("INSERT INTO `users` (username, password, salt, email, phone, name) VALUES (:username, :password, :salt, :email, :phone, :name)");
			$register->execute(array('username' => $user['email'], 'password' => $user['password']['pass'], 'salt' => $user['password']['salt'], 'email' => $user['email'], 'phone' => $user['phone'], 'name' => $user['name']));

			if ($register->rowCount() > 0) {
				echo "rows updated";
			} else {
				echo "Failed";
			}

		} else {

			echo ($this->error[1]['103']['error']);

		}
	}

	// Create User
	public function createUser($info) {
		// Validate user input
		if (isset($info['email'], $info['name'])) {
			
			// Sanitize the user information
			$input = $this->sanitizeInput($info);

			// Make sure data is good before working with DB
			if ($this->validateData($input)) {

				// Check if the user is already registered
				if (!$this->checkUser($input)) {

					// Encrypt password
					$info['password'] = $this->encryptPass($info['name']);

					// Register user
					$this->registerUser($info);

				} else {
					var_dump($this->error[1]['102']);
				}

			} else {
				var_dump($this->error[1]['101']);

				return FALSE;
			}
			// Return 
		}

	}

	// Set last login attempt
	private function setLoginAttempt($username) {
		if (isset($username)) {
			$time = time();

			$attempt = $this->conn->prepare("UPDATE `users` SET last_login_attempt = :attempt WHERE `username` = :username");
			$attempt->execute(array('attempt' => $time, 'username' => $username));

			if ($attempt->rowCount() == 0) {
				// This is for error handling

				echo ('something went wrong with login attempts');
			}
		}
	}

	// Set the number of failed attampts
	private function setFailedAttempt($username, $count) {
		if (isset($username)) {
			$failed = $count + 1;

			$update = $this->conn->prepare("UPDATE `users` SET failed_attempts = :count WHERE `username` = :username");
			$update->execute(array('count' => $failed, 'username' => $username));

			if ($update->rowCount() == 0) {
				// For error handling
				// echo ('something went wrong with failed attampts');
			}
		}
	}

	// Number generateion
	private function randomNumber($min, $max) {
		// Set our range
		$difference = $max - $min;
		// If range is not negative
		if ($difference > 0 ) {
			$bytes = (int) (log($difference, 2) / 8 ) + 1;
			$bits = (int) (log($difference, 2)) + 1;
			$filter = (int) (1 << $bits) - 1;
			do {
				// Generate Random number
				$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes, $s)));
				$rnd = $rnd & $filter;
			} while ($rnd >= $difference);
			// Return our random number
			return $min + $rnd;
		} else {
			// Otherwise, return just the minimum number
			return $min;
		}
	}

	// Generate random tokens
	private function generateToken($length) {
		$token = '';
		$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
		for ($i = 0; $i < $length; $i++) {
			$token .= $string[$this->randomNumber(0, strlen($string))];
		}

		return $token;
	}

	// Set token to DB
	private function setToken($username, $token) {
		$_SESSION['token'] = $token;

		$update = $this->conn->prepare("UPDATE `users` SET `token` = :token WHERE `username` = :username");
		$update->execute(array('token' => $token, 'username' => $username));

		if ($update->rowCount() == 0) {
			// Remove before release. for error handling
			echo ('There was an error handling token');
		}
	}

	// Get Token
	private function getToken($username) {
		$grab = $this->conn->prepare("SELECT `token` FROM `users` WHERE `username` = :username");
		$grab->bindValue(':username', $username);
		$grab->execute();

		if ($grab->rowCount() > 0) {
			return $grab->fetchColumn();
		} else {
			return FALSE;
		}
	}

	// Login User
	public function loginuser($info) {
		// Sanitize information
		if (isset($info['email'], $info['password']) ) {
			// Make sure everything is valid

			$input = $this->sanitizeInput($info);

			if ($this->validateData($input)) {
				// Grab relevent user information from DB
				$user = $this->checkUser($input);

				if ($user) {
					// If there has been five failed attempts, lock account for 15 min
					$delay = ($user['failed_attempts'] < $this->config['user']['default_login_attempts']) ? pow($user['failed_attempts'], 2) : 900;

					// Check last login time, failed logins, and nonce
					if (abs(time() - $user['last_login_attempt']) > $delay) {
						// Set this time as the last login attempt
						$this->setLoginAttempt($input['email']);

						if ($this->encryptPass($input['password'], $user['salt'])['pass'] === $user['password']) {
							// Regenerate session id
							session_regenerate_id(TRUE);

							// If match, set info to active session
							$_SESSION['username'] = $user['email'];
							$_SESSION['timeout'] = (time() + 900);

							// Generate session token
							$token = $this->generateToken(128);

							// Set token to db and session
							$this->setToken($user['email'], $token);

							// Reset failed attempts
							$this->setFailedAttempt($user['email'], -1);

							// Redirect to ssl
							echo ("This is good to redirect now");

						} else {
							// Passwords are bad, figure this out.
							var_dump($this->error[1]['104']['error']);

							// Set failed attempts
							$this->setFailedAttempt($user['email'], $user['failed_attempts']);

						}
						
					} else {
						echo ($this->error[1]['105']['error']);
					}

				} else {
					echo ($this->error[1]['104']['error']);
				}
				
			}

		}

	}

	// Verify Login
	public function verifyUser() {

		// Check that a user is logged in and the timout has not occured.
		if (time() <= $_SESSION['timeout'] && isset($_SESSION['username'])) {
			// Verify token
			if ($_SESSION['token'] == $this->getToken($_SESSION['username']) && strlen($_SESSION['token']) == 128) {
				// Update session timout
				$_SESSION['timeout'] = (time() + 900);

				return TRUE;
			} else {
				// This will be removed
				echo "Error with tokens";
				return FALSE;
			}
		} else {
			// This will be removed as well
			var_dump($error[1]['106']);
			return FALSE;
		}
	}

	// Logout user
	public function logoutUser() {
		session_destroy();
		session_unset();

		$self = $_SERVER['PHP_SELF'];
		header("Refresh: 5; url=$self");
	}

}