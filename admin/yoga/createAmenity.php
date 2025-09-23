<?php
// /admin/yoga/createAmenity.php
// include '../../session.php';
include 'db.php';

$errors = [];
$name = '';
$icon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO yoga_amenities (name, icon, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('ss', $name, $icon);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Amenity created.';
            header('Location: manageAmenities.php');
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/head.php'; ?>
<link href="../css/styles.css" rel="stylesheet">
<body class="sb-nav-fixed">
<?php include '../includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include '../includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Add Amenity</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="card mb-4">
                        <div class="card-header">Amenity Info</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Icon (CSS Class)</label>
                                <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($icon); ?>">
                                <small class="text-muted">Example: fa fa-yoga or any icon class from your icon set</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Create Amenity</button>
                    <a href="manageAmenities.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
