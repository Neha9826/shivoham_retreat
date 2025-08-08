<?php include 'session.php'; ?>
<?php include 'db.php'; ?>

<?php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: allRooms.php");
    exit;
}

$room_id = intval($_GET['id']);

// Fetch room info with creator
$query = "SELECT r.*, e.name AS emp_name 
          FROM rooms r 
          LEFT JOIN emp e ON r.created_by = e.id 
          WHERE r.id = $room_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger m-3'>Room not found.</div>";
    exit;
}

$row = mysqli_fetch_assoc($result);

// Fetch all room images
$imgQuery = "SELECT image_path FROM room_images WHERE room_id = $room_id";
$imgResult = mysqli_query($conn, $imgQuery);

// ✅ Fetch selected amenities
$amenitiesQuery = "
    SELECT a.name, a.icon_class 
    FROM room_amenities ra 
    JOIN amenities a ON ra.amenity_id = a.id 
    WHERE ra.room_id = $room_id
";
$amenitiesResult = mysqli_query($conn, $amenitiesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="mb-0">Room Details</h2>
                            <div>
                                <a href="editRoom.php?id=<?= $room_id ?>" class="btn btn-sm btn-primary me-2">Edit</a>
                                <a href="deleteRoom.php?id=<?= $room_id ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <?php
                            if (mysqli_num_rows($imgResult) > 0) {
                                while ($img = mysqli_fetch_assoc($imgResult)) {
                                    $imgPath = htmlspecialchars($img['image_path']);
                                    echo "<img src='$imgPath' alt='Room Image' style='width: 200px; height: 150px; object-fit: cover; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);'>";
                                }
                            } else {
                                echo "<p class='text-muted'>No images available.</p>";
                            }
                            ?>
                        </div>

                        <!-- Room Info -->
                        <p><strong>Room Name:</strong><br><?= htmlspecialchars($row['room_name']) ?></p>
                        <p><strong>Capacity:</strong><br><?= htmlspecialchars($row['room_capacity']) ?> persons</p>
                        <p><strong>Price per Night (₹):</strong><br>₹<?= number_format($row['price_per_night'], 2) ?></p>
                        <p><strong>Created By:</strong><br><?= htmlspecialchars($row['emp_name']) ?></p>
                        <p><strong>Created At:</strong><br><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
                        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($row['description'])) ?></p>

                        <!-- ✅ Amenities Section -->
                        <div class="mt-4">
                            <p><strong>Amenities:</strong></p>
                            <?php if (mysqli_num_rows($amenitiesResult) > 0): ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php while ($a = mysqli_fetch_assoc($amenitiesResult)): ?>
                                        <span class="badge bg-light text-dark border shadow-sm py-2 px-3" style="font-size: 15px;">
                                            <i class="bi <?= htmlspecialchars($a['icon_class']) ?> me-1"></i>
                                            <?= htmlspecialchars($a['name']) ?>
                                        </span>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No amenities assigned.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
                <div class="text-end mt-4">
                    <a href="allRooms.php" class="btn btn-secondary">← Back to All Rooms</a>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
