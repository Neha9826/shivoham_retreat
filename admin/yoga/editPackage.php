<?php
// /admin/yoga/editPackage.php
include '../../session.php';
include '../../db.php';

$retreat_id = isset($_GET['retreat_id']) ? intval($_GET['retreat_id']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($retreat_id <= 0 || $id <= 0) {
    $_SESSION['flash_error'] = 'Invalid parameters.';
    header('Location: allRetreats.php');
    exit;
}

// Check retreat
$stmt = $conn->prepare("SELECT title FROM yoga_retreats WHERE id = ?");
$stmt->bind_param('i', $retreat_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['flash_error'] = 'Retreat not found.';
    header('Location: allRetreats.php');
    exit;
}
$retreat = $res->fetch_assoc();

// Fetch package
$stmt = $conn->prepare("SELECT * FROM yoga_packages WHERE id = ? AND retreat_id = ?");
$stmt->bind_param('ii', $id, $retreat_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['flash_error'] = 'Package not found.';
    header('Location: managePackages.php?retreat_id=' . $retreat_id);
    exit;
}
$pkg = $res->fetch_assoc();

$errors = [];
$name = $pkg['name'];
$days = $pkg['days'];
$price = $pkg['price'];
$description = $pkg['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $days = intval($_POST['days'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($days <= 0) $errors[] = 'Days must be positive.';
    if ($price <= 0) $errors[] = 'Price must be positive.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE yoga_packages SET name = ?, days = ?, price = ?, description = ? WHERE id = ? AND retreat_id = ?");
        $stmt->bind_param('sidssi', $name, $days, $price, $description, $id, $retreat_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Package updated.';
            header('Location: managePackages.php?retreat_id=' . $retreat_id);
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
                <h2>Edit Package – <?= htmlspecialchars($retreat['title']); ?></h2>

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
                        <div class="card-header">Package Info</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Package Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Days</label>
                                    <input type="number" name="days" class="form-control" value="<?= htmlspecialchars($days); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($price); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($description); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Package</button>
                    <a href="managePackages.php?retreat_id=<?= $retreat_id; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include '../../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
