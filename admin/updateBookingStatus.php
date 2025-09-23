<?php
// admin/updateBookingStatus.php
include 'db.php';
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bookingRequests.php');
    exit;
}

$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$booking_id || $new_status === '') {
    header('Location: bookingRequests.php');
    exit;
}

// Fetch booking details
$stmt = $conn->prepare(
    "SELECT id, status, room_id, check_in, check_out, no_of_rooms
     FROM bookings WHERE id = ?"
);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header('Location: bookingRequests.php');
    exit;
}

$prev_status = $booking['status'];
$room_id     = (int)$booking['room_id'];
$check_in    = $booking['check_in'];
$check_out   = $booking['check_out'];
$no_of_rooms = max(1, (int)$booking['no_of_rooms']);

// Update booking status
$u = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$u->bind_param("si", $new_status, $booking_id);
$u->execute();
$u->close();

// Helper to iterate nights
function iterate_nights($start, $end) {
    $startDate = new DateTime($start);
    $endDate   = new DateTime($end);
    $interval  = new DateInterval('P1D');
    $dates     = [];
    for ($d = clone $startDate; $d < $endDate; $d->add($interval)) {
        $dates[] = $d->format('Y-m-d');
    }
    return $dates;
}

$dates = iterate_nights($check_in, $check_out);

if ($new_status === 'booked' && $prev_status !== 'booked') {
    // Insert availability rows with booking reference and qty
    $conn->begin_transaction();
    try {
        $stmt_ins = $conn->prepare(
            "INSERT INTO room_availability (room_id, date, booked_rooms, booking_id, qty)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                 booked_rooms = booked_rooms + VALUES(booked_rooms),
                 qty = qty + VALUES(qty),
                 booking_id = VALUES(booking_id)"
        );
        foreach ($dates as $d) {
            $stmt_ins->bind_param(
                "isiii",
                $room_id,
                $d,
                $no_of_rooms, // booked_rooms
                $booking_id,  // booking_id reference
                $no_of_rooms  // qty same as booked_rooms
            );
            $stmt_ins->execute();
        }
        $stmt_ins->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Optionally log $e->getMessage()
    }
}

if ($prev_status === 'booked' && $new_status !== 'booked') {
    // Decrement booked rooms for this booking only
    $conn->begin_transaction();
    try {
        $stmt_up = $conn->prepare(
            "UPDATE room_availability
             SET booked_rooms = GREATEST(0, booked_rooms - ?),
                 qty = GREATEST(0, qty - ?)
             WHERE room_id = ? AND date = ? AND booking_id = ?"
        );
        foreach ($dates as $d) {
            $stmt_up->bind_param(
                "iiisi",
                $no_of_rooms,
                $no_of_rooms,
                $room_id,
                $d,
                $booking_id
            );
            $stmt_up->execute();
        }
        $stmt_up->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }
}

header('Location: bookingRequests.php');
exit;
