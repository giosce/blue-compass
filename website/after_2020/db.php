<?php
// debugging setting
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set("log_errors", 1);
//ini_set("error_log", "/tmp/php-error.log");

$properties = parse_ini_file("properties.ini");

$host = $properties["host"];
$username = $properties["username"];
$password = $properties["password"];
$db_name = $properties["db_name"];

$conn = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
	die ('Failed to connect to MySQL: ' . mysqli_connect_error());	
}

?>
