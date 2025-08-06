<?php
session_start();
include 'db.php'; // adjust the path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['check_in']      = $_POST['check_in'];
    $_SESSION['check_out']     = $_POST['check_out'];
    $_SESSION['no_of_rooms'] = $_POST['no_of_rooms'];
    $_SESSION['guests']        = $_POST['guests'];
    $_SESSION['num_children']  = $_POST['num_children'];

    // Redirect to allRooms page with clean URL
    header("Location: allRooms.php");
    exit;
}
?>