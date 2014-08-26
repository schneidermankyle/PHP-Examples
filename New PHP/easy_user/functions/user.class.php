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
			'username' => 'root',
			'password' => 'root',
			'host' => 'localhost',
			'db_name' => 'user_system'
		)
	);

	// Track all of our errors
	private $error = array(
		1 => ''
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
		
			$temp->exec('CREATE DATABASE `' . $this->config['db']['db_name'] . '`;');

			// Release the temp PDO for garbage collection
			unset($temp);

			// Re-run the construct method to ensure our connection object is set and pointing to proper PDO
			$this->constructDb();

		} catch (Exception $e) {

			die("There was an error connecting to the database");

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

			return $conn;
		}
	}

	// construct sets up everything we need, such as the db config
	public function __construct() {
		$this->conn = $this->constructDb();
		
	}

	// Check to see if user is already in the system
	private function checkUser($user) {
		
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
						$email = strip_tags($field);
						// Do some more 
						$input['email'] = $email;
						break;
					case 'name':
						$name = strip_tags($field);
						// Do some more
						$input['name'] = $name;
						break;
				}
			}

			return $input;
		}
	}

	// Create User
	public function createUser($info) {
		// Validate user input
		if ($info['email'] && $info['name']) {
			
			// Sanitize the user information
			$input = $this->sanitizeInput($info);

			// Test
			var_dump($input);

			// Check if the user is already registered

			// Register user

			// Return 

		}


		// Input user information into the database 
	}


	// Login user

	// Verify Login

	// Logout user






}