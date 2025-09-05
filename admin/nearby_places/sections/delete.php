<?php
// admin/nearby_places/sections/delete.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'error' => ''];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM nearby_places_sections WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
        $response['message'] = 'Section and all its images deleted successfully.';
    } else {
        $response['error'] = 'Database query failed: ' . mysqli_error($conn);
    }
} else {
    $response['error'] = 'Invalid section ID.';
}

echo json_encode($response);
?>