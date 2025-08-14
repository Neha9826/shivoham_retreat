<?php
include '../session.php';
include '../db.php';

try {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception("Invalid ID");
    }

    // Get image to delete
    $rs = $conn->query("SELECT featured_image FROM blogs WHERE id = $id");
    if ($row = $rs->fetch_assoc()) {
        $imagePath = '../' . ltrim(str_replace('\\', '/', $row['featured_image']), '/');
        if (!empty($row['featured_image']) && file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete blog
    $conn->query("DELETE FROM blogs WHERE id = $id");

    header("Location: ../allBlogs.php?success=" . urlencode("Blog deleted successfully."));
    exit;
} catch (Exception $e) {
    header("Location: ../allBlogs.php?error=" . urlencode($e->getMessage()));
    exit;
}
