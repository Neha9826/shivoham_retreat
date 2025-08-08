<?php
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allRooms.php");
    exit;
}

$room_id = intval($_GET['id']);
$room_result = mysqli_query($conn, "SELECT * FROM rooms WHERE id = $room_id");
$room = mysqli_fetch_assoc($room_result);

if (!$room) {
    echo "Room not found.";
    exit;
}

// ✅ Fetch amenities
$allAmenities = mysqli_query($conn, "SELECT * FROM amenities ORDER BY name ASC");
$roomAmenities = [];
$amenityResult = mysqli_query($conn, "SELECT amenity_id FROM room_amenities WHERE room_id = $room_id");
while ($row = mysqli_fetch_assoc($amenityResult)) {
    $roomAmenities[] = $row['amenity_id'];
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['room_name'];
    $capacity = $_POST['room_capacity'];
    $price = $_POST['price_per_night'];
    $desc = $_POST['description'];

    $update_query = "UPDATE rooms SET room_name='$name', room_capacity='$capacity', price_per_night='$price', description='$desc' WHERE id=$room_id";
    mysqli_query($conn, $update_query);

    // ✅ Update amenities
    mysqli_query($conn, "DELETE FROM room_amenities WHERE room_id = $room_id");
    if (!empty($_POST['amenities'])) {
        foreach ($_POST['amenities'] as $aid) {
            $aid = intval($aid);
            mysqli_query($conn, "INSERT INTO room_amenities (room_id, amenity_id) VALUES ($room_id, $aid)");
        }
    }

    // ✅ Upload new images
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/rooms/';
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = basename($_FILES['images']['name'][$key]);
            $target = $upload_dir . time() . '_' . $filename;
            move_uploaded_file($tmp_name, $target);

            mysqli_query($conn, "INSERT INTO room_images (room_id, image_path) VALUES ($room_id, '$target')");
        }
    }

    header("Location: roomDetails.php?id=$room_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Room</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>Room Name</label>
                        <input type="text" name="room_name" class="form-control" value="<?= htmlspecialchars($room['room_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Capacity</label>
                        <input type="number" name="room_capacity" class="form-control" value="<?= $room['room_capacity'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Price Per Night</label>
                        <input type="number" name="price_per_night" class="form-control" value="<?= $room['price_per_night'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" required><?= htmlspecialchars($room['description']) ?></textarea>
                    </div>

                    <!-- ✅ Amenities -->
                    <div class="mb-3">
                        <label class="form-label">Amenities</label><br>
                        <?php while ($a = mysqli_fetch_assoc($allAmenities)): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['id'] ?>"
                                    <?= in_array($a['id'], $roomAmenities) ? 'checked' : '' ?> id="amenity<?= $a['id'] ?>">
                                <label class="form-check-label" for="amenity<?= $a['id'] ?>">
                                    <i class="bi <?= $a['icon_class'] ?>"></i> <?= htmlspecialchars($a['name']) ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="mb-3">
                        <label>Upload New Images</label>
                        <input type="file" name="images[]" class="form-control" multiple>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Room</button>
                    <a href="allRooms.php" class="btn btn-secondary">Cancel</a>
                </form>

                <h5 class="mt-4">Existing Images</h5>
                <div class="row">
                    <?php
                    $img_query = mysqli_query($conn, "SELECT * FROM room_images WHERE room_id = $room_id");
                    while ($img = mysqli_fetch_assoc($img_query)) {
                        echo '<div class="col-md-3 mb-3">';
                        echo '<img src="' . $img['image_path'] . '" class="img-fluid rounded">';
                        echo '<a href="deleteRoomImage.php?id=' . $img['id'] . '&room_id=' . $room_id . '" class="btn btn-sm btn-danger d-block mt-2">Delete</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>