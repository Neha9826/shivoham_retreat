<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include 'db.php';

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$photos = [];
if ($room_id > 0) {
    $stmt = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $path = $row['image_path'];
        $photos[] = (str_starts_with($path, 'uploads/') ? 'admin/' : '') . $path;
    }
    $stmt->close();
}

echo json_encode($photos);
