<?php
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allRooms.php");
    exit;
}

$room_id = $_GET['id'];

// Delete images from server
$img_query = mysqli_query($conn, "SELECT image_path FROM room_images WHERE room_id = $room_id");
while ($img = mysqli_fetch_assoc($img_query)) {
    if (file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }
}
mysqli_query($conn, "DELETE FROM room_images WHERE room_id = $room_id");

// Delete room
mysqli_query($conn, "DELETE FROM rooms WHERE id = $room_id");

header("Location: allRooms.php");
exit;
?>
