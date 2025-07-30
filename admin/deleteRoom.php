<?php
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allRooms.php");
    exit;
}

$room_id = (int) $_GET['id']; // prevent SQL injection

// 1. Delete related booking requests
mysqli_query($conn, "DELETE FROM booking_requests WHERE room_id = $room_id");

// 2. Delete related confirmed bookings
mysqli_query($conn, "DELETE FROM bookings WHERE room_id = $room_id");

// 3. Delete room images from server
$img_query = mysqli_query($conn, "SELECT image_path FROM room_images WHERE room_id = $room_id");
while ($img = mysqli_fetch_assoc($img_query)) {
    if (file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }
}

// 4. Delete room images from DB
mysqli_query($conn, "DELETE FROM room_images WHERE room_id = $room_id");

// 5. Finally, delete the room
mysqli_query($conn, "DELETE FROM rooms WHERE id = $room_id");

header("Location: allRooms.php");
exit;
?>
