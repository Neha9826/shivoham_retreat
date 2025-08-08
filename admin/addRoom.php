<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_POST['submit'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $room_capacity = intval($_POST['room_capacity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price_per_night = floatval($_POST['price_per_night']);
    $total_rooms = intval($_POST['total_rooms']);
    $created_by = $_SESSION['emp_id'];

    $insertRoom = "INSERT INTO rooms (room_name, room_capacity, description, price_per_night, created_by, total_rooms)
                   VALUES ('$room_name', $room_capacity, '$description', $price_per_night, $created_by, $total_rooms)";
    if (mysqli_query($conn, $insertRoom)) {
        $room_id = mysqli_insert_id($conn);

        // ✅ Save amenities
        if (!empty($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $amenity_id) {
                $aid = intval($amenity_id);
                mysqli_query($conn, "INSERT INTO room_amenities (room_id, amenity_id) VALUES ($room_id, $aid)");
            }
        }

        // ✅ Handle multiple image uploads
        if (!empty($_FILES['room_images']['name'][0])) {
            $uploadDir = "uploads/rooms/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['room_images']['tmp_name'] as $key => $tmp_name) {
                $fileName = basename($_FILES['room_images']['name'][$key]);
                $targetFilePath = $uploadDir . time() . '_' . $fileName;

                if (move_uploaded_file($tmp_name, $targetFilePath)) {
                    $imagePath = mysqli_real_escape_string($conn, $targetFilePath);
                    mysqli_query($conn, "INSERT INTO room_images (room_id, image_path) VALUES ($room_id, '$imagePath')");
                }
            }
        }

        header("Location: allRooms.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// ✅ Fetch amenities
$amenityResult = mysqli_query($conn, "SELECT * FROM amenities ORDER BY name ASC");
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
                <h2>Add New Room</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Room Name</label>
                        <input type="text" name="room_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Capacity</label>
                        <input type="number" name="room_capacity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price per Night (₹)</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Number of Rooms</label>
                        <input type="number" name="total_rooms" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- ✅ Amenities -->
                    <div class="mb-3">
                        <label class="form-label">Amenities</label><br>
                        <?php while ($row = mysqli_fetch_assoc($amenityResult)): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $row['id'] ?>" id="amenity<?= $row['id'] ?>">
                                <label class="form-check-label" for="amenity<?= $row['id'] ?>">
                                    <i class="bi <?= htmlspecialchars($row['icon_class']) ?>"></i> <?= htmlspecialchars($row['name']) ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Images</label>
                        <input type="file" name="room_images[]" class="form-control" multiple>
                        <small class="text-muted">You can select multiple images.</small>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Add Room</button>
                    <a href="allRooms.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
