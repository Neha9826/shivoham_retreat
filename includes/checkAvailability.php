<?php
session_start();
include '../db.php'; // adjust the path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = $_POST['name'];
    $checkin_date  = $_POST['check_in'];
    $checkout_date = $_POST['check_out'];
    $adults        = $_POST['adults'];
    $children      = $_POST['children'];
    $room_id       = $_POST['room_id'] ?? null;
    $email         = $_POST['email'];
    $phone         = $_POST['phone'];

    // Save to booking_requests
    $stmt = $conn->prepare("INSERT INTO booking_requests (name, check_in, check_out, adults, children, room_id, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissss", $name, $checkin_date, $checkout_date, $adults, $children, $room_id, $email, $phone);
    $stmt->execute();
    $stmt->close();

    // Save dates in session for availability check in allRooms.php
    $_SESSION['check_in'] = $checkin_date;
    $_SESSION['check_out'] = $checkout_date;

    header("Location: ../allRooms.php");
    exit;
}
?>
