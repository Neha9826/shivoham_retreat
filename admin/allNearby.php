<?php
// admin/allNearby.php - debug-friendly version (temporary)

include 'session.php';
include 'db.php';

// Turn on errors for debugging right now
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Optional: include admin helpers if available
if (file_exists(__DIR__ . '/includes/helpers.php')) {
    include __DIR__ . '/includes/helpers.php';
}

// Handle delete action for the main place
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT main_image FROM nearby_places_main WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $place = $result->fetch_assoc();

    if ($place) {
        $deleteQuery = "DELETE FROM nearby_places_main WHERE id = $id";
        if (mysqli_query($conn, $deleteQuery)) {
            if (!empty($place['main_image']) && file_exists(__DIR__ . '/../' . $place['main_image'])) {
                @unlink(__DIR__ . '/../' . $place['main_image']);
            }
            header("Location: allNearby.php?message=success");
            exit;
        } else {
            header("Location: allNearby.php?message=error");
            exit;
        }
    }
}

// Main fetch
$sql = "SELECT * FROM nearby_places_main ORDER BY id DESC"; // simpler ordering for debug
$places = mysqli_query($conn, $sql);
if (!$places) {
    die("SQL Error: " . $conn->error . "<br>SQL: " . htmlspecialchars($sql));
}

$message = '';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'success') $message = "Nearby place deleted successfully.";
    elseif ($_GET['message'] === 'error') $message = "Failed to delete nearby place.";
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
                            <th>Google Maps Link</th>
                            
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($places) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($places)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>

                                    <td>
                                        <?php
                                            $img = $row['main_image'] ?? '';
                                            if (!empty($img)) {
                                                // build URL using admin helper (safe)
                                                $imgUrl = build_image_url($img);
                                                // show both preview and stored path for debugging
                                                echo '<div style="max-width:140px">';
                                                echo '<img src="' . htmlspecialchars(build_image_url($row['main_image'])) . '" alt="" style="width:120px; height:auto; display:block; margin-bottom:4px;">
                                                    ';
                                                echo '</div>';
                                            } else {
                                                echo '<span class="text-muted">No Image</span>';
                                            }
                                        ?>
                                    </td>

                                    <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($row['Maps_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['Maps_link']) ?>" target="_blank">Link</a>
                                            
                                        <?php else: ?>
                                            <span class="text-muted">No Link</span>
                                        <?php endif; ?>
                                    </td>

                                    

                                    <td>
                                        <a href="editNearby.php?id=<?= urlencode($row['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="allNearby.php?delete=<?= urlencode($row['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this nearby place and all its sections and images?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No nearby places found.</td></tr>
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
