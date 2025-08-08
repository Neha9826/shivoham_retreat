<?php
include 'db.php';
$roomId = (int) ($_GET['room_id'] ?? 0);
$response = [];

$rq = mysqli_query($conn, "
  SELECT id, room_name, room_capacity, total_rooms, price_per_night, description
  FROM rooms WHERE id = $roomId
");
if ($room = mysqli_fetch_assoc($rq)) {
  $response['id'] = $room['id'];
  $response['name'] = $room['room_name'];
  $response['capacity'] = $room['room_capacity'];
  $response['price'] = $room['price_per_night'];
  $response['description'] = $room['description'];

  // availability
  $check_in = $_SESSION['check_in'] ?? '';
  $check_out = $_SESSION['check_out'] ?? '';
  if ($check_in && $check_out) {
    $bookedQ = mysqli_query($conn, "
      SELECT COUNT(*) AS booked_count
      FROM booking_rooms br
      JOIN bookings b ON br.booking_id = b.id
      WHERE br.room_id = $roomId
      AND ('$check_in' < b.check_out AND '$check_out' > b.check_in)
    ");
    $booked = mysqli_fetch_assoc($bookedQ)['booked_count'] ?? 0;
    $response['available'] = max(0, $room['total_rooms'] - $booked);
  } else {
    $response['available'] = $room['total_rooms'];
  }

  // images
  $imgQ = mysqli_query($conn, "SELECT image_path FROM room_images WHERE room_id = $roomId");
  $response['photos'] = [];
  while ($img = mysqli_fetch_assoc($imgQ)) {
    $response['photos'][] = 'admin/' . $img['image_path'];
  }

  // amenities
  $amQ = mysqli_query($conn, "
    SELECT a.name, a.icon_class FROM amenities a
    JOIN room_amenities ra ON ra.amenity_id = a.id
    WHERE ra.room_id = $roomId
  ");
  $response['amenities'] = [];
  while ($am = mysqli_fetch_assoc($amQ)) {
    $response['amenities'][] = [
      'name' => $am['name'],
      'icon_class' => $am['icon_class'] ?: 'bi-check-circle'
    ];
  }

  echo json_encode($response);
} else {
  http_response_code(404);
  echo json_encode(['error' => 'Room not found']);
}
?>
