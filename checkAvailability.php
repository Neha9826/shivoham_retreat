<?php
include 'db.php';

header('Content-Type: application/json');

$room_id   = $_POST['room_id'] ?? null;
$checkin   = $_POST['checkin'] ?? null;
$checkout  = $_POST['checkout'] ?? null;
$adults    = intval($_POST['adults'] ?? 0);
$children  = intval($_POST['children'] ?? 0);
$rooms     = intval($_POST['rooms'] ?? 1);

if(!$room_id || !$checkin || !$checkout){
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Fetch room details
$roomQuery = $conn->prepare("SELECT total_rooms, max_capacity, max_extra_with_bed FROM rooms WHERE id = ?");
$roomQuery->bind_param("i", $room_id);
$roomQuery->execute();
$result = $roomQuery->get_result();
if($result->num_rows == 0){
    echo json_encode(['status' => 'error', 'message' => 'Room not found']);
    exit;
}
$room = $result->fetch_assoc();

$totalCapacity = ($room['max_capacity'] + $room['max_extra_with_bed']) * $rooms;

// Total guests excluding children under 5
$totalGuests = $adults + $children;

// Check if guest count exceeds room capacity
if($totalGuests > $totalCapacity){
    echo json_encode(['status' => 'error', 'message' => 'Selected rooms cannot accommodate this many guests']);
    exit;
}

// Check availability for dates
$bookingQuery = $conn->prepare("
    SELECT SUM(no_of_rooms) as booked_rooms 
    FROM bookings 
    WHERE room_id = ? 
      AND NOT (checkout_date <= ? OR checkin_date >= ?)
");
$bookingQuery->bind_param("iss", $room_id, $checkin, $checkout);
$bookingQuery->execute();
$bookingResult = $bookingQuery->get_result();
$booked = $bookingResult->fetch_assoc()['booked_rooms'] ?? 0;

// Calculate available rooms
$availableRooms = $room['total_rooms'] - $booked;

if($availableRooms <= 0){
    echo json_encode(['status' => 'error', 'message' => 'No rooms available for selected dates']);
} else {
    echo json_encode([
        'status' => 'success',
        'available' => $availableRooms,
        'message' => "$availableRooms room(s) available"
    ]);
}
?>
