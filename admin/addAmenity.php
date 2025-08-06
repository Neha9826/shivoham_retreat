<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_POST['submit'])) {
    $amenity_name = mysqli_real_escape_string($conn, $_POST['name']);
    $icon_class   = mysqli_real_escape_string($conn, $_POST['icon_class']);

    $insertQuery = "INSERT INTO amenities (name, icon_class) VALUES ('$amenity_name', '$icon_class')";
    if (mysqli_query($conn, $insertQuery)) {
        header("Location: allAmenities.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Add New Amenity</h2>
                <form method="POST" id="amenityForm">
                    <div class="mb-3">
                        <label class="form-label">Amenity Name</label>
                        <input type="text" name="name" id="amenityName" class="form-control" placeholder="e.g. Wi-Fi, Parking" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Auto-Suggested Icon</label><br>
                        <i id="iconPreview" class="bi bi-question-circle" style="font-size: 24px;"></i>
                        <input type="hidden" name="icon_class" id="iconClass" value="bi-question-circle">
                        <small class="form-text text-muted">Icon is auto-selected based on name.</small>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Add Amenity</button>
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
    "gym": "bi-dumbbell",
    "spa": "bi-heart-pulse",
    "restaurant": "bi-cup-straw",
    "breakfast": "bi-cup-hot",
    "coffee": "bi-cup-hot",
    "lift": "bi-box-arrow-up",
    "elevator": "bi-box-arrow-up",
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
    let bestMatch = "bi-question-circle"; // default
    for (let keyword in amenityNameToIcon) {
        if (input.includes(keyword)) {
            bestMatch = amenityNameToIcon[keyword];
            break;
        }
    }
    document.getElementById('iconPreview').className = "bi " + bestMatch;
    document.getElementById('iconClass').value = bestMatch;
});
</script>

</body>
</html>
