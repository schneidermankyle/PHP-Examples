<?php


// General needs of the page
require './functions/config.php';


// Page routing
$base_url = dirname($_SERVER['PHP_SELF']);
$page = substr($_SERVER['REQUEST_URI'], strlen($base_url) + 2 );

$page = explode('?', $page);
$page = $page[0];
$page = trim($page, "/");



if (!$page) {
	
	// set to home if no page detected
	$page = 'home';

} 

// Set our html body to the requested page.
$page_path = "include/$page.php";

include './include/header.php';
include $page_path;
include './include/footer.php';