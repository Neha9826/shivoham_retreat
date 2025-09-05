<?php
// admin/nearby_places/images/insert.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'error' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nearby_place_section_id'])) {
    $sectionId = intval($_POST['nearby_place_section_id']);
    $uploadDir = '../../uploads/nearby_places/';
    $uploadedCount = 0;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName    = time() . '_' . basename($_FILES['images']['name'][$key]);
                $destination = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $destination)) {
                    $imagePath = 'uploads/nearby_places/' . $fileName;
                    $sql = "INSERT INTO nearby_places_images (nearby_place_section_id, image_path) VALUES ($sectionId, '$imagePath')";
                    if (mysqli_query($conn, $sql)) {
                        $uploadedCount++;
                    }
                }
            }
        }
    }

    if ($uploadedCount > 0) {
        $response['success'] = true;
        $response['message'] = "{$uploadedCount} image(s) uploaded successfully.";
    } else {
        $response['error'] = 'No images were uploaded or a database error occurred.';
    }

} else {
    $response['error'] = 'Invalid request.';
}

echo json_encode($response);
?>