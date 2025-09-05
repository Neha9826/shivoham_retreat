<?php
// admin/nearby_places/sections/insert.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'error' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $place_id     = mysqli_real_escape_string($conn, $_POST['nearby_place_id']);
    $side_heading = mysqli_real_escape_string($conn, $_POST['side_heading']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $section_id   = mysqli_real_escape_string($conn, $_POST['section_id']);

    if (!empty($section_id)) {
        // Update existing section
        $sql = "UPDATE nearby_places_sections SET side_heading='$side_heading', description='$description' WHERE id=$section_id";
        $response['message'] = 'Section updated successfully.';
    } else {
        // Insert new section
        $sql = "INSERT INTO nearby_places_sections (nearby_place_id, side_heading, description) VALUES ('$place_id', '$side_heading', '$description')";
        $response['message'] = 'Section added successfully.';
    }
    
    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Database query failed: ' . mysqli_error($conn);
    }
} else {
    $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
?>