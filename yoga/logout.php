<?php
session_start();
unset($_SESSION['yoga_user_id'], $_SESSION['yoga_user_name']);
header("Location: index.php");
exit;
