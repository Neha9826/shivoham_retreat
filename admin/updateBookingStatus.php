<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    $status = $_POST['status'] ?? '';

    if ($booking_id && $status) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: bookingRequests.php");
exit;
