<?php
include 'session.php';
include 'db.php';

$amenity_id = intval($_GET['id'] ?? 0);
if ($amenity_id <= 0) {
    header("Location: allAmenities.php");
    exit;
}

// Fetch existing amenity
$result = mysqli_query($conn, "SELECT * FROM amenities WHERE id = $amenity_id LIMIT 1");
$amenity = mysqli_fetch_assoc($result);
if (!$amenity) {
    echo "Amenity not found.";
    exit;
}

// List of icons
$icon_map = [
    'wifi' => 'bi-wifi',
    'parking' => 'bi-car-front-fill',
    'pool' => 'bi-water',
    'spa' => 'bi-droplet',
    'gym' => 'bi-dumbbell',
    'ac' => 'bi-snow2',
    'tv' => 'bi-tv',
    'bar' => 'bi-cup-straw',
    'restaurant' => 'bi-cup-hot',
    'bathroom' => 'bi-badge-wc',
    'safe' => 'bi-shield-lock',
    'garden' => 'bi-tree',
    'coffee' => 'bi-cup-hot',
    'laundry' => 'bi-basket',
    'fan' => 'bi-fan',
];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? '');

    if (empty($name)) {
        $error = "Amenity name is required.";
    } else {
        // Auto-suggest icon if left empty
        if (empty($icon_class)) {
            foreach ($icon_map as $key => $icon) {
                if (stripos($name, $key) !== false) {
                    $icon_class = $icon;
                    break;
                }
            }
            if (empty($icon_class)) {
                $icon_class = 'bi-star'; // fallback icon
            }
        }

        $stmt = $conn->prepare("UPDATE amenities SET name = ?, icon_class = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $icon_class, $amenity_id);
        if ($stmt->execute()) {
            header("Location: allAmenities.php");
            exit;
        } else {
            $error = "Update failed: " . $stmt->error;
        }
    }
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
                <h2>Edit Amenity</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Amenity Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($amenity['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon Class (Optional)</label>
                        <input type="text" name="icon_class" class="form-control" value="<?= htmlspecialchars($amenity['icon_class']) ?>" placeholder="e.g. bi-wifi">
                        <div class="form-text">Leave blank to auto-generate based on name.</div>
                    </div>
                    <div class="mb-3">
                        <label>Preview:</label><br>
                        <i class="bi <?= htmlspecialchars($amenity['icon_class']) ?>" style="font-size: 2rem;"></i>
                    </div>
                    <button type="submit" class="btn btn-success">Update Amenity</button>
                    <a href="allAmenities.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Include Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<?php include 'includes/script.php'; ?>
</body>
</html>
