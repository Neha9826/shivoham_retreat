<?php
// /admin/yoga/editAmenity.php
include '../../session.php';
include '../../db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid amenity ID.';
    header('Location: manageAmenities.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM yoga_amenities WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['flash_error'] = 'Amenity not found.';
    header('Location: manageAmenities.php');
    exit;
}
$amenity = $res->fetch_assoc();

$errors = [];
$name = $amenity['name'];
$icon = $amenity['icon'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE yoga_amenities SET name=?, icon=? WHERE id=?");
        $stmt->bind_param('ssi', $name, $icon, $id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Amenity updated.';
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
<?php include '../../includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include '../../includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include '../../includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Amenity</h2>

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

                    <button type="submit" class="btn btn-primary">Update Amenity</button>
                    <a href="manageAmenities.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include '../../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
