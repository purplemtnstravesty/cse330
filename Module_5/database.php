<?php
// Database connection using MySQLi
$host = 'localhost';        
$username = 'jess';
$password = 'WestPoint2014!!';
$dbname = 'NewsAggDB';

$mysqli = new mysqli($host, $username, $password, $dbname);

// Check if connection succeeded
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
?>