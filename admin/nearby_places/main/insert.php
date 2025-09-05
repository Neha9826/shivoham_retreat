<?php
// admin/nearby_places/main/insert.php
include '../../session.php';
include '../../db.php';

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title         = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : '';
    $mapsLink      = isset($_POST['google_maps_link']) ? mysqli_real_escape_string($conn, $_POST['google_maps_link']) : '';
    $mainImagePath = '';

    $uploadDir = '../../uploads/nearby_places/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Correctly reference the 'main_image' form field
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $fileName    = time() . '_' . basename($_FILES['main_image']['name']);
        $destination = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
            $mainImagePath = 'uploads/nearby_places/' . $fileName; // Path to store in DB
        }
    }

    // This is the corrected INSERT query with the correct column names
    $sql = "INSERT INTO nearby_places_main (title, Maps_link, main_image) VALUES ('$title', '$mapsLink', '$mainImagePath')";

    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        echo json_encode(["success" => true, "id" => $last_id, "message" => "Main details saved successfully!"]);
    } else {
        echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
    }
}
?>