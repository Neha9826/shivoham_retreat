<?php
$host = "localhost";
$user = "u738048941_root1";
$pass = "Ss@#2025"; 
$db = "u738048941_shivoham";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
