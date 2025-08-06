<?php
include 'session.php';
include 'db.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM amenities WHERE id = $id");
    header("Location: allAmenities.php");
    exit;
}

// Fetch all amenities
$result = mysqli_query($conn, "SELECT * FROM amenities ORDER BY id DESC");
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
                <h2>All Amenities</h2>
                <a href="addAmenity.php" class="btn btn-primary mb-3">+ Add New Amenity</a>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Icon</th>
                                <th>Preview</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><code><?= htmlspecialchars($row['icon_class']) ?></code></td>
                                    <td><i class="bi <?= htmlspecialchars($row['icon_class']) ?>" style="font-size: 1.5rem;"></i></td>
                                    <td>
                                        <a href="editAmenity.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="allAmenities.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this amenity?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($result) === 0): ?>
                                <tr><td colspan="5" class="text-center">No amenities found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
<!-- Include Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
