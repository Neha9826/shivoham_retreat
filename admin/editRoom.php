<?php
include 'session.php';
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allRooms.php");
    exit;
}

$room_id = intval($_GET['id']);

// Fetch room details
$roomQuery = mysqli_query($conn, "SELECT * FROM rooms WHERE id=$room_id");
if (mysqli_num_rows($roomQuery) == 0) {
    header("Location: allRooms.php");
    exit;
}
$room = mysqli_fetch_assoc($roomQuery);

// Fetch amenities
$amenityResult = mysqli_query($conn, "SELECT * FROM amenities ORDER BY name ASC");

// Fetch selected amenities
$selectedAmenities = [];
$selAmen = mysqli_query($conn, "SELECT amenity_id FROM room_amenities WHERE room_id=$room_id");
while ($row = mysqli_fetch_assoc($selAmen)) $selectedAmenities[] = $row['amenity_id'];

// Fetch room images
$imageResult = mysqli_query($conn, "SELECT * FROM room_images WHERE room_id=$room_id");

// Fetch seasonal prices
$seasonalResult = mysqli_query($conn, "SELECT * FROM room_seasonal_prices WHERE room_id=$room_id ORDER BY start_date ASC");

if (isset($_POST['submit'])) {
    // Room fields
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $base_adults = intval($_POST['base_adults']);
    $max_extra_with_bed = intval($_POST['max_extra_with_bed']);
    $max_child_without_bed_5_12 = intval($_POST['max_child_without_bed_5_12']);
    $max_child_without_bed_below_5 = intval($_POST['max_child_without_bed_below_5']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $total_rooms = intval($_POST['total_rooms']);
    $room_capacity = $base_adults + $max_extra_with_bed + $max_child_without_bed_5_12;

    // Pricing
    $standard_price = floatval($_POST['standard_price']);
    $price_with_breakfast = floatval($_POST['price_with_breakfast']);
    $price_with_breakfast_lunch = floatval($_POST['price_with_breakfast_lunch']);
    $price_with_all_meals = floatval($_POST['price_with_all_meals']);
    $price_with_extra_bed = floatval($_POST['price_with_extra_bed']);
    $price_child_5_12 = floatval($_POST['price_child_5_12']);
    $price_child_below_5 = floatval($_POST['price_child_below_5']);

    // Update room
    $updateRoom = "UPDATE rooms SET
        room_name='$room_name',
        base_adults=$base_adults,
        max_extra_with_bed=$max_extra_with_bed,
        max_child_without_bed_5_12=$max_child_without_bed_5_12,
        max_child_without_bed_below_5=$max_child_without_bed_below_5,
        room_capacity=$room_capacity,
        description='$description',
        total_rooms=$total_rooms,
        standard_price=$standard_price,
        price_with_breakfast=$price_with_breakfast,
        price_with_breakfast_lunch=$price_with_breakfast_lunch,
        price_with_all_meals=$price_with_all_meals,
        price_with_extra_bed=$price_with_extra_bed,
        price_child_5_12=$price_child_5_12,
        price_child_below_5=$price_child_below_5
        WHERE id=$room_id";
    mysqli_query($conn, $updateRoom);

    // Update amenities
    mysqli_query($conn, "DELETE FROM room_amenities WHERE room_id=$room_id");
    if (!empty($_POST['amenities'])) {
        foreach ($_POST['amenities'] as $amenity_id) {
            mysqli_query($conn, "INSERT INTO room_amenities (room_id, amenity_id) VALUES ($room_id, " . intval($amenity_id) . ")");
        }
    }

    // Handle new images
    if (!empty($_FILES['room_images']['name'][0])) {
        $uploadDir = "uploads/rooms/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['room_images']['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($_FILES['room_images']['name'][$key]);
            $targetFilePath = $uploadDir . time() . '_' . $fileName;
            if (move_uploaded_file($tmp_name, $targetFilePath)) {
                $imagePath = mysqli_real_escape_string($conn, "admin/" . $targetFilePath);
                mysqli_query($conn, "INSERT INTO room_images (room_id, image_path) VALUES ($room_id, '$imagePath')");
            }
        }
    }
    
    // Handle image deletions
    if (!empty($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_id) {
            $image_id = intval($image_id);
            $img_path_query = "SELECT image_path FROM room_images WHERE id = $image_id";
            $img_path_result = mysqli_query($conn, $img_path_query);
            $img_path_row = mysqli_fetch_assoc($img_path_result);

            if ($img_path_row && file_exists($img_path_row['image_path'])) {
                unlink($img_path_row['image_path']); // Delete file from server
                mysqli_query($conn, "DELETE FROM room_images WHERE id = $image_id"); // Delete from DB
            }
        }
    }

    // Handle seasonal prices
    if (!empty($_POST['seasonal'])) {
        foreach ($_POST['seasonal'] as $index => $season) {
            $start_date = mysqli_real_escape_string($conn, $season['start_date']);
            $end_date = mysqli_real_escape_string($conn, $season['end_date']);
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $categories = ['standard', 'breakfast', 'breakfast_lunch', 'all_meals'];

            $values = [];
            foreach ($days as $day) {
                foreach ($categories as $cat) {
                    $key = $day . '_' . $cat;
                    $values[$key] = isset($season[$key]) ? floatval($season[$key]) : 0;
                }
            }

            if (!empty($season['id'])) {
                $season_id = intval($season['id']);
                $updateQuery = "UPDATE room_seasonal_prices SET 
                    start_date='$start_date', end_date='$end_date', ";
                foreach ($values as $col => $val) {
                    $updateQuery .= "`$col`=$val, ";
                }
                $updateQuery = rtrim($updateQuery, ", ") . " WHERE id=$season_id";
                mysqli_query($conn, $updateQuery);
            } else {
                $cols = implode(',', array_map(function($val) { return "`$val`"; }, array_keys($values)));
                $vals = implode(',', array_values($values));
                mysqli_query($conn, "INSERT INTO room_seasonal_prices (room_id, start_date, end_date, $cols)
                    VALUES ($room_id, '$start_date', '$end_date', $vals)");
            }
        }
    }

    header("Location: allRooms.php");
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
                <h2>Edit Room: <?= htmlspecialchars($room['room_name']) ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
                    <div class="mb-3">
                        <label class="form-label">Room Name</label>
                        <input type="text" name="room_name" class="form-control" required value="<?= htmlspecialchars($room['room_name']) ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Base Adults</label>
                            <input type="number" id="base_adults" name="base_adults" class="form-control" min="1" value="<?= $room['base_adults'] ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Max Adult/Child with Extra Bed</label>
                            <input type="number" name="max_extra_with_bed" class="form-control" min="0" value="<?= $room['max_extra_with_bed'] ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Child (5–12) without Bed</label>
                            <input type="number" name="max_child_without_bed_5_12" class="form-control" min="0" value="<?= $room['max_child_without_bed_5_12'] ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Child (&lt;5) without Bed</label>
                            <input type="number" name="max_child_without_bed_below_5" class="form-control" min="0" value="<?= $room['max_child_without_bed_below_5'] ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Total Room Capacity</label>
                            <input type="number" id="room_capacity" name="room_capacity" class="form-control" readonly value="<?= $room['room_capacity'] ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Number of Rooms</label>
                            <input type="number" name="total_rooms" class="form-control" min="1" value="<?= $room['total_rooms'] ?>" required>
                        </div>
                    </div>

                    <h5 class="mt-3">Pricing</h5>
                    <div class="row">
                        <?php
                        $pricingFields = [
                            'standard_price' => 'Standard Price (₹)',
                            'price_with_breakfast' => 'Price with Breakfast (₹)',
                            'price_with_breakfast_lunch' => 'Price with Breakfast + Lunch (₹)',
                            'price_with_all_meals' => 'Price with All Meals (₹)',
                            'price_with_extra_bed' => 'Price with Extra Bed (₹)',
                            'price_child_5_12' => 'Child 5–12 Price (₹)',
                            'price_child_below_5' => 'Child <5 Price (₹)'
                        ];
                        foreach ($pricingFields as $field => $label) {
                            echo '<div class="col-md-3 mb-3">';
                            echo '<label>' . $label . '</label>';
                            echo '<input type="number" step="0.01" name="' . $field . '" class="form-control" value="' . $room[$field] . '">';
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($room['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amenities</label><br>
                        <?php while ($row = mysqli_fetch_assoc($amenityResult)): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $row['id'] ?>"
                                       id="amenity<?= $row['id'] ?>" <?= in_array($row['id'], $selectedAmenities) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="amenity<?= $row['id'] ?>">
                                    <i class="bi <?= htmlspecialchars($row['icon_class']) ?>"></i> <?= htmlspecialchars($row['name']) ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Images</label>
                        <div class="row mb-2">
                            <?php while ($img = mysqli_fetch_assoc($imageResult)): ?>
                                <div class="col-md-2 text-center">
                                    <!-- <img src="<?= htmlspecialchars($img['image_path']) ?>" class="img-thumbnail mb-1" style="width:100%;height:auto;"> -->
                                    <img src="<?= htmlspecialchars(str_replace('admin/', '', $img['image_path'])) ?>" class="img-thumbnail mb-1" style="width:100%;height:auto;">
                                    <a href="deleteRoomImage.php?id=<?= $img['id'] ?>&room_id=<?= $room_id ?>" class="btn btn-sm btn-danger d-block" onclick="return confirm('Are you sure you want to delete this image?');">Delete</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <input type="file" name="room_images[]" class="form-control" multiple>
                    </div>

                    <h5 class="mt-4">Seasonal Prices</h5>
                    <div id="seasonal_prices_container">
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $categories = ['standard', 'breakfast', 'breakfast_lunch', 'all_meals'];
                        $defaultPrices = [
                            'standard' => $room['standard_price'],
                            'breakfast' => $room['price_with_breakfast'],
                            'breakfast_lunch' => $room['price_with_breakfast_lunch'],
                            'all_meals' => $room['price_with_all_meals']
                        ];

                        if (mysqli_num_rows($seasonalResult) > 0):
                            while ($season = mysqli_fetch_assoc($seasonalResult)):
                                ?>
                                <div class="seasonal-row mb-2 border p-2">
                                    <input type="hidden" name="seasonal[<?= $season['id'] ?>][id]" value="<?= $season['id'] ?>">
                                    <div class="row g-2 mb-1">
                                        <div class="col-md-2"><label>Start Date</label><input type="date" name="seasonal[<?= $season['id'] ?>][start_date]" class="form-control" value="<?= $season['start_date'] ?>"></div>
                                        <div class="col-md-2"><label>End Date</label><input type="date" name="seasonal[<?= $season['id'] ?>][end_date]" class="form-control" value="<?= $season['end_date'] ?>"></div>
                                    </div>

                                    <?php foreach ($days as $day): ?>
                                        <div class="row g-2 mb-1">
                                            <div class="col-md-12"><strong><?= $day ?></strong></div>
                                            <?php foreach ($categories as $cat):
                                                $value = $season[$day . '_' . $cat] !== null ? $season[$day . '_' . $cat] : $defaultPrices[$cat];
                                                ?>
                                                <div class="col-md-3">
                                                    <input type="number" step="0.01" name="seasonal[<?= $season['id'] ?>][<?= $day ?>_<?= $cat ?>]" 
                                                           class="form-control" placeholder="<?= ucfirst(str_replace('_', ' ', $cat)) ?>" value="<?= $value ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php
                            endwhile;
                        endif;
                        ?>
                    </div>
                    <button type="button" id="add_seasonal_row" class="btn btn-sm btn-success mb-3">Add New Seasonal Price</button>

                    <div class="mb-3">
                        <button type="submit" name="submit" class="btn btn-primary">Update Room</button>
                        <a href="allRooms.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Auto-update capacity
        const baseAdults = document.getElementById("base_adults");
        const extraWithBed = document.querySelector("[name='max_extra_with_bed']");
        const child5to12 = document.querySelector("[name='max_child_without_bed_5_12']");
        const capacityField = document.getElementById("room_capacity");

        function updateCapacity() {
            const base = parseInt(baseAdults.value) || 0;
            const withBed = parseInt(extraWithBed.value) || 0;
            const c5_12 = parseInt(child5to12.value) || 0;
            capacityField.value = base + withBed + c5_12;
        }
        baseAdults.addEventListener("input", updateCapacity);
        extraWithBed.addEventListener("input", updateCapacity);
        child5to12.addEventListener("input", updateCapacity);
        updateCapacity();

        // Add new seasonal row
        const seasonalContainer = document.getElementById("seasonal_prices_container");
        document.getElementById("add_seasonal_row").addEventListener("click", function () {
            const idx = Date.now();
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const categories = ['standard', 'breakfast', 'breakfast_lunch', 'all_meals'];
            const defaultPrices = {
                'standard': <?= $room['standard_price'] ?>,
                'breakfast': <?= $room['price_with_breakfast'] ?>,
                'breakfast_lunch': <?= $room['price_with_breakfast_lunch'] ?>,
                'all_meals': <?= $room['price_with_all_meals'] ?>
            };

            const div = document.createElement("div");
            div.classList.add("seasonal-row", "mb-2", "border", "p-2");

            let html = `<div class="row g-2 mb-1">
                            <div class="col-md-2"><label>Start Date</label><input type="date" name="seasonal[new${idx}][start_date]" class="form-control" required></div>
                            <div class="col-md-2"><label>End Date</label><input type="date" name="seasonal[new${idx}][end_date]" class="form-control" required></div>
                        </div>`;

            days.forEach(day => {
                html += `<div class="row g-2 mb-1"><div class="col-md-12"><strong>${day}</strong></div>`;
                categories.forEach(cat => {
                    html += `<div class="col-md-3">
                                <input type="number" step="0.01" name="seasonal[new${idx}][${day}_${cat}]" 
                                       class="form-control" placeholder="${cat.replace('_', ' ')}" value="${defaultPrices[cat]}">
                            </div>`;
                });
                html += `</div>`;
            });
            html += `<button type="button" class="btn btn-sm btn-danger mt-2 remove-seasonal-row">Remove</button>`;

            div.innerHTML = html;
            seasonalContainer.appendChild(div);
        });

        // Remove seasonal row
        seasonalContainer.addEventListener("click", function(e) {
            if (e.target.classList.contains('remove-seasonal-row')) {
                e.target.closest('.seasonal-row').remove();
            }
        });
    });
</script>

<?php include 'includes/script.php'; ?>
</body>
</html>
