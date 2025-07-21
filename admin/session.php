<?php
session_start();
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit;
}
?>
