<?php include 'session.php'; ?>
<?php include 'db.php'; ?>

<?php
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger m-3'>Invalid room ID.</div>";
    exit;
}

$room_id = intval($_GET['id']);

// Room info
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

// Images
$imgQuery = "SELECT image_path FROM room_images WHERE room_id = $room_id";
$imgResult = mysqli_query($conn, $imgQuery);

// Amenities
$amenitiesQuery = "
    SELECT a.name, a.icon_class
    FROM room_amenities ra
    JOIN amenities a ON ra.amenity_id = a.id
    WHERE ra.room_id = $room_id
";
$amenitiesResult = mysqli_query($conn, $amenitiesQuery);

// Seasonal Prices
$seasonalQuery = "SELECT * FROM room_seasonal_prices WHERE room_id = $room_id ORDER BY start_date DESC";
$seasonalResult = mysqli_query($conn, $seasonalQuery);

// Default meal plan prices
$defaultPrices = [
    'Standard' => $row['standard_price'],
    'Breakfast' => $row['price_with_breakfast'],
    'Breakfast+Lunch' => $row['price_with_breakfast_lunch'],
    'All Meals' => $row['price_with_all_meals']
];

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$mealPlans = ['standard'=>'Standard','breakfast'=>'Breakfast','breakfast_lunch'=>'Breakfast+Lunch','all_meals'=>'All Meals'];
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
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
                            <h2>Room Details</h2>
                            <div>
                                <a href="editRoom.php?id=<?= $room_id ?>" class="btn btn-sm btn-primary me-2">Edit</a>
                                <a href="deleteRoom.php?id=<?= $room_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <?php
                            if (mysqli_num_rows($imgResult) > 0) {
                                while ($img = mysqli_fetch_assoc($imgResult)) {
                                    // ✅ UPDATED PATH LOGIC
                                    echo "<img src='".htmlspecialchars(str_replace('admin/', '', $img['image_path']))."' style='width:200px;height:150px;object-fit:cover;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.2);'>";
                                }
                            } else {
                                echo "<p class='text-muted'>No images available.</p>";
                            }
                            ?>
                        </div>

                        <p><strong>Room Name:</strong> <?= htmlspecialchars($row['room_name']) ?></p>
                        <p><strong>Total Room Capacity:</strong><br><?= htmlspecialchars($row['room_capacity']) ?> persons</p>
                        <ul>
                            <li>Base Adults: <?= htmlspecialchars($row['base_adults']) ?></li>
                            <li>Max Adult/Child with Extra Bed: <?= htmlspecialchars($row['max_extra_with_bed']) ?></li>
                            <li>Child (5–12) without Bed: <?= htmlspecialchars($row['max_child_without_bed_5_12']) ?></li>
                            <li>Child (&lt;5) without Bed: <?= htmlspecialchars($row['max_child_without_bed_below_5']) ?></li>
                        </ul>

                        <h5 class="mt-4">Prices per Night (₹)</h5>
                        <table class="table table-bordered table-striped text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Guest Type</th>
                                    <th>Standard</th>
                                    <th>With Breakfast</th>
                                    <th>Breakfast + Lunch</th>
                                    <th>All Meals</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Room Price</strong></td>
                                    <td><?= number_format($row['standard_price'], 2) ?></td>
                                    <td><?= number_format($row['price_with_breakfast'], 2) ?></td>
                                    <td><?= number_format($row['price_with_breakfast_lunch'], 2) ?></td>
                                    <td><?= number_format($row['price_with_all_meals'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Extra Bed</strong></td>
                                    <td><?= number_format($row['price_with_extra_bed_standard'], 2) ?></td>
                                    <td><?= number_format($row['price_with_extra_bed_bf'], 2) ?></td>
                                    <td><?= number_format($row['price_with_extra_bed_bf_lunch'], 2) ?></td>
                                    <td><?= number_format($row['price_with_extra_bed_all_meals'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Child (5-12)</strong></td>
                                    <td><?= number_format($row['price_child_5_12_standard'], 2) ?></td>
                                    <td><?= number_format($row['price_child_5_12_bf'], 2) ?></td>
                                    <td><?= number_format($row['price_child_5_12_bf_lunch'], 2) ?></td>
                                    <td><?= number_format($row['price_child_5_12_all_meals'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Child (&lt;5)</strong></td>
                                    <td colspan="4"><?= number_format($row['price_child_below_5'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <p><strong>Total Rooms:</strong><br><?= htmlspecialchars($row['total_rooms']) ?></p>

                        <p><strong>Created By:</strong> <?= htmlspecialchars($row['emp_name']) ?></p>
                        <p><strong>Created At:</strong> <?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></p>
                        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($row['description'])) ?></p>

                        <div class="mt-4">
                            <p><strong>Amenities:</strong></p>
                            <?php if (mysqli_num_rows($amenitiesResult) > 0): ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php while ($a = mysqli_fetch_assoc($amenitiesResult)): ?>
                                        <span class="badge bg-light text-dark border shadow-sm py-2 px-3">
                                            <i class="bi <?= htmlspecialchars($a['icon_class']) ?>"></i> <?= htmlspecialchars($a['name']) ?>
                                        </span>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No amenities assigned.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <h5>Seasonal Prices</h5>
                            <table class="table table-bordered table-striped text-center align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th rowspan="2">SL</th>
                                        <th colspan="2">Season</th>
                                        <th rowspan="2">Meal Plan</th>
                                        <th rowspan="2">Default</th>
                                        <?php foreach($days as $day): ?>
                                            <th rowspan="2"><?= htmlspecialchars($day) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sl = 1;
                                    if (mysqli_num_rows($seasonalResult) > 0):
                                        mysqli_data_seek($seasonalResult, 0);
                                        while ($sp = mysqli_fetch_assoc($seasonalResult)):
                                            $firstRow = true;
                                            foreach ($mealPlans as $key => $label): ?>
                                                <tr <?= $firstRow ? 'class="table-light"' : '' ?>>
                                                    <?php if ($firstRow): ?>
                                                        <td rowspan="<?= count($mealPlans) ?>"><?= $sl++ ?></td>
                                                        <td rowspan="<?= count($mealPlans) ?>"><?= htmlspecialchars($sp['start_date']) ?></td>
                                                        <td rowspan="<?= count($mealPlans) ?>"><?= htmlspecialchars($sp['end_date']) ?></td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars($label) ?></td>
                                                    <td>₹<?= number_format($defaultPrices[$label], 2) ?></td>
                                                    <?php
                                                    foreach ($days as $day):
                                                        // try exact DB column first (e.g., "Monday_standard")
                                                        $colExact = $day . '_' . $key;
                                                        // fallback possibility if your columns are lowercase (e.g., "monday_standard")
                                                        $colLower = strtolower($colExact);

                                                        $val = null;
                                                        if (array_key_exists($colExact, $sp)) {
                                                            $val = $sp[$colExact];
                                                        } elseif (array_key_exists($colLower, $sp)) {
                                                            $val = $sp[$colLower];
                                                        }

                                                        if ($val === null || $val === '') {
                                                            $val = $defaultPrices[$label]; // only fallback if truly empty
                                                        }
                                                    ?>
                                                        <td>₹<?= number_format((float)$val, 2) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php $firstRow = false; ?>
                                            <?php endforeach; // meal plans
                                        endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="<?= 5 + count($days) ?>" class="text-center">No seasonal prices defined.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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