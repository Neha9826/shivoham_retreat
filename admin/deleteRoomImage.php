<?php
include 'db.php';

if (isset($_GET['id']) && isset($_GET['room_id'])) {
    $img_id = $_GET['id'];
    $room_id = $_GET['room_id'];

    $query = "SELECT image_path FROM room_images WHERE id = $img_id";
    $result = mysqli_query($conn, $query);
    $img = mysqli_fetch_assoc($result);

    if ($img && file_exists($img['image_path'])) {
        unlink($img['image_path']);
    }

    mysqli_query($conn, "DELETE FROM room_images WHERE id = $img_id");

    header("Location: editRoom.php?id=$room_id");
    exit;
}
?>
