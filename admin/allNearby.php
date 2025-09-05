<?php
include 'session.php';
include 'db.php';

// Handle delete action for the main place
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // First, get the main image path to delete the file
    $stmt = $conn->prepare("SELECT main_image FROM nearby_places_main WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $place = $result->fetch_assoc();

    if ($place) {
        $deleteQuery = "DELETE FROM nearby_places_main WHERE id = $id";
        if (mysqli_query($conn, $deleteQuery)) {
            // Delete the associated main image file from the server
            if ($place['main_image'] && file_exists('../' . $place['main_image'])) {
                @unlink('../' . $place['main_image']);
            }
            header("Location: allNearby.php?message=success");
            exit;
        } else {
            header("Location: allNearby.php?message=error");
            exit;
        }
    }
}

$places = mysqli_query($conn, "SELECT * FROM nearby_places_main ORDER BY created_at DESC");

$message = '';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'success') {
        $message = "Nearby place deleted successfully.";
    } elseif ($_GET['message'] === 'error') {
        $message = "Failed to delete nearby place.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>All Nearby Places</h2>
            <a href="addNearby.php" class="btn btn-primary mb-3">+ Add New Nearby Place</a>
            <?php if ($message): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($places) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($places)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><img src="../<?= htmlspecialchars($row['main_image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" style="width: 100px;"></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><a href="<?= htmlspecialchars($row['google_maps_link']) ?>" target="_blank">Link</a></td>
                                    <td>
                                        <a href="editNearby.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="allNearby.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this nearby place and all its sections and images?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No nearby places found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>