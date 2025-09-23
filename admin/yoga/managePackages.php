<?php
// /admin/yoga/managePackages.php
include '../../session.php';
include '../../db.php';

$current_page = 'admin/yoga/managePackages.php';

$retreat_id = isset($_GET['retreat_id']) ? intval($_GET['retreat_id']) : 0;
if ($retreat_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid retreat ID.';
    header('Location: allRetreats.php');
    exit;
}

// Fetch retreat info
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

// Fetch packages
$packages = $conn->query("SELECT * FROM yoga_packages WHERE retreat_id = $retreat_id ORDER BY created_at DESC");
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
                <h2>Manage Packages – <?= htmlspecialchars($retreat['title']); ?></h2>
                <?php if (isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
                <?php endif; ?>

                <a href="createPackage.php?retreat_id=<?= $retreat_id; ?>" class="btn btn-primary mb-3">Add New Package</a>

                <div class="card mb-4">
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Package Name</th>
                                    <th>Days</th>
                                    <th>Price</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($packages && $packages->num_rows > 0): ?>
                                    <?php while ($pkg = $packages->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pkg['name']); ?></td>
                                            <td><?= (int)$pkg['days']; ?></td>
                                            <td><?= number_format($pkg['price'], 2); ?></td>
                                            <td><?= htmlspecialchars($pkg['created_at']); ?></td>
                                            <td>
                                                <a href="editPackage.php?id=<?= $pkg['id']; ?>&retreat_id=<?= $retreat_id; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="deletePackage.php?id=<?= $pkg['id']; ?>&retreat_id=<?= $retreat_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this package?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No packages found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <a href="allRetreats.php" class="btn btn-secondary">Back to Retreats</a>
            </div>
        </main>
        <?php include '../../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
