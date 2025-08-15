<?php
$host = "localhost";
$user = "u738048941_root1";
$pass = "Ss@#2025"; 
$db = "u738048941_shivoham";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
