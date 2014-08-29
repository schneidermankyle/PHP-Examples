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
	private function createDb() {
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
				`email` VARCHAR(254) NULL,
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
					case 'name':
						$name = filter_var(strip_tags($field), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
						// Do some more
						$input['name'] = (preg_match("/^(([A-za-z]+[\s]{1}[A-za-z]+)|([A-Za-z]+))$/m", $name)) ? $name : FALSE;
						break;
					default:
						// Decide what to do with the array.
						echo "There was an error with the form data";
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
			return FALSE;
		} else {
			return TRUE;
		}
	}

	private function encryptPass($password) {
		// Encrypt password //
		// Grab PW

		// Generate CSPRNG salt
		$salt = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM);
		// Convert strings to arrays for processing
		// Figure out which is shorter PW or SALT
		if ($salt <= $password) {
			$left = str_split($salt);
			$right = str_split($password);
		} else {
			$left = str_split($password);
			$right = str_split($salt);
		}
		
		$i = 0;

		// Loop through the smaller string 

		foreach($left as $char) {
			// For each character, run the algorithm and figure out where to insert current char in the larger string
			// 
			echo (ord($char) . "\n");
			echo "$i";
			$i++;
		}

		
		
		// Hash pw+salt with bcrypt

	}

	private function registerUser($user) {
		// process password

		$register = $this->conn->prepare("INSERT INTO `users` (username, password, email) VALUES (:username, :password, :email)");
	}

	// Create User
	public function createUser($info) {
		// Validate user input
		if ($info['email'] && $info['name']) {
			
			// Sanitize the user information
			$input = $this->sanitizeInput($info);

			// Make sure data is good before working with DB
			if ($this->validateData($input)) {

				// Check if the user is already registered
				if ($this->checkUser($input)) {

					// Register user
					$this->encryptPass($info['name']);
					
				}
			} else {
				var_dump($this->error[1]);

				return FALSE;
			}
			// Return 

		}


		// Input user information into the database 
	}


	// Login user

	// Verify Login

	// Logout user






}