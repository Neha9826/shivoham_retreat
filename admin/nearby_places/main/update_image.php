<?php
// admin/nearby_places/main/update_image.php
include '../../session.php';
include '../../db.php';
include '../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_FILES['main_image'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$placeId = intval($_POST['id']);

// fetch current record
$stmt = $conn->prepare("SELECT main_image FROM nearby_places_main WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("i", $placeId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$oldImage = $row['main_image'] ?? null;
$stmt->close();

// Determine project root
$projectRoot = __DIR__;
for ($i = 0; $i < 6; $i++) {
    if (is_dir($projectRoot . '/uploads')) break;
    $parent = dirname($projectRoot);
    if ($parent === $projectRoot) break;
    $projectRoot = $parent;
}

$uploadFolder = 'uploads/nearby_places/';
$uploadDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . '/' . trim($uploadFolder, '/') . '/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Delete old file if exists
if (!empty($oldImage)) {
    $old = ltrim(str_replace(['\\','//'], '/', $oldImage), '/');
    $oldFull = rtrim($projectRoot, DIRECTORY_SEPARATOR) . '/' . $old;
    if (file_exists($oldFull)) {
        @unlink($oldFull);
    }
}

// Handle upload
if ($_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['main_image']['tmp_name'];
    $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
    $newName = time() . '_' . uniqid() . '.' . strtolower($ext);
    $destination = $uploadDir . $newName;
    if (move_uploaded_file($tmpName, $destination)) {
        $relativePath = rtrim($uploadFolder, '/') . '/' . $newName;
        $u = $conn->prepare("UPDATE nearby_places_main SET main_image = ? WHERE id = ?");
        if ($u) {
            $u->bind_param("si", $relativePath, $placeId);
            if ($u->execute()) {
                echo json_encode(['success' => true, 'message' => 'Main image updated.', 'image_path' => $relativePath, 'image_path_full' => build_image_url($relativePath)]);
            } else {
                @unlink($destination);
                echo json_encode(['success' => false, 'error' => $u->error]);
            }
            $u->close();
        } else {
            @unlink($destination);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'File upload error: ' . $_FILES['main_image']['error']]);
}
