<?php
include 'db.php';

$room_id = intval($_GET['room_id'] ?? 0);
$capacity = 0;

$res = mysqli_query($conn, "SELECT room_capacity FROM rooms WHERE id = $room_id");
if ($row = mysqli_fetch_assoc($res)) {
    $capacity = intval($row['room_capacity']);
}

echo $capacity;
?>
