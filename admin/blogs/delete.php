<?php
include '../session.php';
include '../db.php';

header('Content-Type: application/json');

try {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) throw new Exception("Invalid ID");

    // Get image to delete
    $rs = $conn->query("SELECT featured_image FROM blogs WHERE id = $id");
    if ($row = $rs->fetch_assoc()) {
        if (!empty($row['featured_image']) && file_exists('../' . $row['featured_image'])) {
            unlink('../' . $row['featured_image']);
        }
    }

    $conn->query("DELETE FROM blogs WHERE id = $id");

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
