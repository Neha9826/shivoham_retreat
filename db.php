<?php
$host = "localhost";
$user = "root";
$pass = ""; // Use your MySQL password if any
$db = "shivoham_retreat";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
