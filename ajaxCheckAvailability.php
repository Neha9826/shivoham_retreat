<?php
include 'db.php';

$room_id   = intval($_GET['room_id']);
$check_in  = $_GET['check_in'];
$check_out = $_GET['check_out'];

$response = [
    'available' => true,
    'available_rooms' => null,
    'message' => ''
];

if ($room_id && $check_in && $check_out) {
    // Get total rooms
    $stmt = $conn->prepare("SELECT total_rooms FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $totalRooms = $res['total_rooms'];
    $stmt->close();

    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $minAvailable = $totalRooms;

    while ($start < $end) {
        $dateStr = $start->format('Y-m-d');
        $stmt = $conn->prepare("SELECT booked_rooms FROM room_availability WHERE room_id = ? AND date = ?");
        $stmt->bind_param("is", $room_id, $dateStr);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $booked = $res['booked_rooms'] ?? 0;
        $stmt->close();

        $availableRooms = $totalRooms - $booked;
        if ($availableRooms < $minAvailable) {
            $minAvailable = $availableRooms;
        }

        if ($availableRooms <= 0) {
            $response['available'] = false;
            $response['available_rooms'] = 0;
            $response['message'] = "No rooms available for the selected date range.";
            break;
        }

        $start->modify('+1 day');
    }

    if ($response['available']) {
        $response['available_rooms'] = $minAvailable;
        $response['message'] = "$minAvailable room(s) available for the selected date range.";
    }
}

header('Content-Type: application/json');
echo json_encode($response);
