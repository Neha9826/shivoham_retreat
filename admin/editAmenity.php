<?php
include 'session.php';
include 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: allAmenities.php");
    exit;
}

$amenity_id = intval($_GET['id']);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon_class']);

    $updateQuery = "UPDATE amenities SET name = '$name', icon_class = '$icon' WHERE id = $amenity_id";
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: allAmenities.php");
        exit;
    } else {
        echo "Error updating: " . mysqli_error($conn);
    }
}

// Fetch existing data
$result = mysqli_query($conn, "SELECT * FROM amenities WHERE id = $amenity_id");
if (!$result || mysqli_num_rows($result) == 0) {
    echo "Amenity not found.";
    exit;
}
$amenity = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Amenity</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Amenity</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Amenity Name</label>
                        <input type="text" name="name" id="amenityName" class="form-control" value="<?= htmlspecialchars($amenity['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Auto-Suggested Icon</label><br>
                        <i id="iconPreview" class="bi <?= htmlspecialchars($amenity['icon_class']) ?>" style="font-size: 24px;"></i>
                        <input type="hidden" name="icon_class" id="iconClass" value="<?= htmlspecialchars($amenity['icon_class']) ?>">
                        <small class="form-text text-muted">Icon updates automatically as you type.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Amenity</button>
                    <a href="allAmenities.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>

<script>
const amenityNameToIcon = {
    "wifi": "bi-wifi",
    "parking": "bi-car-front",
    "air conditioning": "bi-snow2",
    "ac": "bi-snow2",
    "television": "bi-tv",
    "tv": "bi-tv",
    "pool": "bi-water",
    "gym": "bi-person-standing",
    "yoga": "bi-person-standing",
    "exercise": "bi-person-walking",
    "bar": "bi-cup",
    "minibar": "bi-cup-hot",
    "breakfast": "bi-cup-hot",
    "laundry": "bi-basket",
    "cleaning": "bi-broom",
    "garden": "bi-tree",
    "elevator": "bi-building-up",
    "lift": "bi-building-up",
    "spa": "bi-heart-pulse",
    "restaurant": "bi-cup-straw",
    "coffee": "bi-cup-hot",
    "wheelchair": "bi-universal-access",
    "pets": "bi-paw",
    "security": "bi-shield-lock",
    "locker": "bi-lock",
    "heater": "bi-thermometer-half",
    "fire extinguisher": "bi-fire",
    "toiletries": "bi-droplet",
    "bathroom": "bi-bucket",
    "balcony": "bi-house",
    "bed": "bi-house-door",
    "room service": "bi-bell",
    "fan": "bi-wind",
    "hot water": "bi-droplet-half",
    "first aid": "bi-bandage",
    "sofa": "bi-reception-3"
};

document.getElementById('amenityName').addEventListener('input', function () {
    const input = this.value.toLowerCase().trim();
    let matchedIcon = "bi-question-circle";
    for (let key in amenityNameToIcon) {
        if (input.includes(key)) {
            matchedIcon = amenityNameToIcon[key];
            break;
        }
    }
    document.getElementById('iconPreview').className = "bi " + matchedIcon;
    document.getElementById('iconClass').value = matchedIcon;
});
</script>

</body>
</html>
