<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_POST['submit'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $base_adults = intval($_POST['base_adults']);
    $max_extra_with_bed = intval($_POST['max_extra_with_bed']);
    $max_child_without_bed_5_12 = intval($_POST['max_child_without_bed_5_12']);
    $max_child_without_bed_below_5 = intval($_POST['max_child_without_bed_below_5']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $total_rooms = intval($_POST['total_rooms']);
    $created_by = $_SESSION['emp_id'];

    // ✅ Pricing fields
    $standard_price = floatval($_POST['standard_price']);
    $price_with_breakfast = floatval($_POST['price_with_breakfast']);
    $price_with_breakfast_lunch = floatval($_POST['price_with_breakfast_lunch']);
    $price_with_all_meals = floatval($_POST['price_with_all_meals']);

    // ✅ NEW Pricing for extra bed and child per meal plan
    $extra_bed_price_standard = floatval($_POST['extra_bed_price_standard']);
    $extra_bed_price_bf = floatval($_POST['extra_bed_price_bf']);
    $extra_bed_price_bf_lunch = floatval($_POST['extra_bed_price_bf_lunch']);
    $extra_bed_price_all_meals = floatval($_POST['extra_bed_price_all_meals']);

    $child_5_12_price_standard = floatval($_POST['child_5_12_price_standard']);
    $child_5_12_price_bf = floatval($_POST['child_5_12_price_bf']);
    $child_5_12_price_bf_lunch = floatval($_POST['child_5_12_price_bf_lunch']);
    $child_5_12_price_all_meals = floatval($_POST['child_5_12_price_all_meals']);

    // ✅ Existing prices
    $price_child_below_5 = floatval($_POST['price_child_below_5']);

    // ✅ Calculate room capacity (ignore <5 yrs)
    $room_capacity = $base_adults + $max_extra_with_bed + $max_child_without_bed_5_12;

    $insertRoom = "INSERT INTO rooms 
        (room_name, base_adults, max_extra_with_bed, max_child_without_bed_5_12, max_child_without_bed_below_5, 
         room_capacity, description, created_by, total_rooms, 
         standard_price, price_with_breakfast, price_with_breakfast_lunch, price_with_all_meals,
         price_with_extra_bed_standard, price_with_extra_bed_bf, price_with_extra_bed_bf_lunch, price_with_extra_bed_all_meals,
         price_child_5_12_standard, price_child_5_12_bf, price_child_5_12_bf_lunch, price_child_5_12_all_meals,
         price_child_below_5)
        VALUES 
        ('$room_name', $base_adults, $max_extra_with_bed, $max_child_without_bed_5_12, $max_child_without_bed_below_5,
         $room_capacity, '$description', $created_by, $total_rooms,
         $standard_price, $price_with_breakfast, $price_with_breakfast_lunch, $price_with_all_meals,
         $extra_bed_price_standard, $extra_bed_price_bf, $extra_bed_price_bf_lunch, $extra_bed_price_all_meals,
         $child_5_12_price_standard, $child_5_12_price_bf, $child_5_12_price_bf_lunch, $child_5_12_price_all_meals,
         $price_child_below_5)";
    
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
            // ✅ CORRECTED UPLOAD DIRECTORY
            $uploadDir = "uploads/rooms/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['room_images']['tmp_name'] as $key => $tmp_name) {
                $fileName = basename($_FILES['room_images']['name'][$key]);
                $targetFilePath = $uploadDir . time() . '_' . $fileName;

                if (move_uploaded_file($tmp_name, $targetFilePath)) {
                    // ✅ SAVE THE CORRECT PATH TO DATABASE
                    $imagePath = mysqli_real_escape_string($conn, "admin/" . $targetFilePath);
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

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Base Adults</label>
                            <input type="number" id="base_adults" name="base_adults" class="form-control" min="1" value="2" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Max Adult/Child with Extra Bed</label>
                            <input type="number" name="max_extra_with_bed" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Child (5–12) without Bed</label>
                            <input type="number" name="max_child_without_bed_5_12" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Child (&lt;5) without Bed</label>
                            <input type="number" name="max_child_without_bed_below_5" class="form-control" min="0" value="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Total Room Capacity</label>
                            <input type="number" id="room_capacity" name="room_capacity" class="form-control" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Number of Rooms</label>
                            <input type="number" name="total_rooms" class="form-control" min="1" required>
                        </div>
                    </div>

                    <h5 class="mt-3">Room Price per Night</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Standard Price (₹)</label>
                            <input type="number" step="0.01" name="standard_price" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Price with Breakfast (₹)</label>
                            <input type="number" step="0.01" name="price_with_breakfast" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Price with Breakfast + Lunch (₹)</label>
                            <input type="number" step="0.01" name="price_with_breakfast_lunch" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Price with All Meals (₹)</label>
                            <input type="number" step="0.01" name="price_with_all_meals" class="form-control">
                        </div>
                    </div>

                    <h5 class="mt-3">Extra Bed Price per Night</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Standard (₹)</label>
                            <input type="number" step="0.01" name="extra_bed_price_standard" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With Breakfast (₹)</label>
                            <input type="number" step="0.01" name="extra_bed_price_bf" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With Breakfast + Lunch (₹)</label>
                            <input type="number" step="0.01" name="extra_bed_price_bf_lunch" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With All Meals (₹)</label>
                            <input type="number" step="0.01" name="extra_bed_price_all_meals" class="form-control" value="0">
                        </div>
                    </div>

                    <h5 class="mt-3">Child (5-12) Price per Night</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Standard (₹)</label>
                            <input type="number" step="0.01" name="child_5_12_price_standard" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With Breakfast (₹)</label>
                            <input type="number" step="0.01" name="child_5_12_price_bf" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With Breakfast + Lunch (₹)</label>
                            <input type="number" step="0.01" name="child_5_12_price_bf_lunch" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>With All Meals (₹)</label>
                            <input type="number" step="0.01" name="child_5_12_price_all_meals" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Child (&lt;5) Price per Night (₹)</label>
                        <input type="number" step="0.01" name="price_child_below_5" class="form-control" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

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
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Add Room</button>
                    <a href="allRooms.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
// Helper function to handle number field input
function handleNumberInput(input) {
    input.addEventListener('input', function() {
        // Remove leading zeros
        if (this.value.length > 1 && this.value.startsWith('0')) {
            this.value = parseInt(this.value, 10);
        }
    });
    input.addEventListener('blur', function() {
        // If the field is empty, set it to 0
        if (this.value === '' || this.value === null) {
            this.value = 0;
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    const baseAdults = document.getElementById("base_adults");
    const extraWithBed = document.querySelector("[name='max_extra_with_bed']");
    const child5to12 = document.querySelector("[name='max_child_without_bed_5_12']");
    const capacityField = document.getElementById("room_capacity");

    // Apply the new function to all number input fields
    document.querySelectorAll('input[type="number"]').forEach(handleNumberInput);

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
});
</script>

<?php include 'includes/script.php'; ?>
</body>
</html>