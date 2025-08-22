<?php
// getRoomDetailsForModal.php (AJAX endpoint)
header('Content-Type: application/json');

// 📌 YOU MUST UPDATE THIS PATH WITH YOUR SUBFOLDER NAME
$basePath = ''; // For example: '/my-hotel-project/' or '/hotel/'

// ✅ Added check for db.php inclusion
if (file_exists('db.php')) {
    include 'db.php';
} else {
    echo json_encode(['error' => 'Database configuration file not found.']);
    exit;
}

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$room_id = $_GET['room_id'] ?? 0;
if (!$room_id) {
    echo json_encode(['error' => 'No room ID provided.']);
    exit;
}

// Fetch room details, images, and amenities
$sql = "SELECT r.room_name, r.description,
               (SELECT GROUP_CONCAT(image_path) FROM room_images WHERE room_id = r.id) AS image_paths,
               (SELECT GROUP_CONCAT(a.name, '|', a.icon_class)
                  FROM amenities a
                  JOIN room_amenities ra ON ra.amenity_id = a.id
                 WHERE ra.room_id = r.id) AS amenity_data
        FROM rooms r WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare SQL statement.']);
    exit;
}

$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(['error' => 'Room not found.']);
    exit;
}

// Process images
$images = [];
if (!empty($room['image_paths'])) {
    $images = array_map(function($path) use ($basePath) {
        if (strpos($path, 'admin/') !== 0 && strpos($path, 'assets/') !== 0) {
            return $basePath . 'admin/' . $path;
        }
        return $basePath . $path;
    }, explode(',', $room['image_paths']));
}

// Process amenities
$amenityList = [];
if (!empty($room['amenity_data'])) {
    $pairs = explode(',', $room['amenity_data']);
    foreach ($pairs as $pair) {
        [$name, $icon] = explode('|', $pair);
        $amenityList[] = ['name' => $name, 'icon' => $icon ?: 'bi-check-circle'];
    }
}

echo json_encode([
    'room_name' => $room['room_name'],
    'description' => $room['description'],
    'images' => $images,
    'amenities' => $amenityList
]);

$stmt->close();
$conn->close();
?>