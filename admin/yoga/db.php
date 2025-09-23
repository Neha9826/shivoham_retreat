<?php
// Database configuration
$host = "localhost";
$user = "root";
$password = ""; // your MySQL password
$dbname = "shivoham_retreat"; // your database name

// Create connection
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
