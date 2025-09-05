<?php
// admin/nearby_places/images/delete.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'error' => ''];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // First, get the file path to delete the physical file
    $result = mysqli_query($conn, "SELECT image_path FROM nearby_places_images WHERE id = $id");
    if ($row = mysqli_fetch_assoc($result)) {
        $filePath = '../../' . $row['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Then, delete the database record
    $sql = "DELETE FROM nearby_places_images WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
        $response['message'] = 'Image deleted successfully.';
    } else {
        $response['error'] = 'Database query failed: ' . mysqli_error($conn);
    }
} else {
    $response['error'] = 'Invalid image ID.';
}

echo json_encode($response);
?>