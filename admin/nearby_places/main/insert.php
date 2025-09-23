<?php
include '../../session.php';
include '../../db.php';
header('Content-Type: application/json; charset=utf-8');

$placeId = isset($_POST['place_id']) ? intval($_POST['place_id']) : 0;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$maps_link = $_POST['google_maps_link'] ?? '';

if (empty($title)) {
    echo json_encode(['success'=>false, 'error'=>'Title required']);
    exit;
}

// Handle main image if uploaded
$mainImagePath = '';
if (!empty($_FILES['main_image']['name'])) {
    $uploadFolder = $_SERVER['DOCUMENT_ROOT'].'/uploads/nearby_places/';
    if (!file_exists($uploadFolder)) mkdir($uploadFolder, 0777, true);

    $filename = time().'_'.basename($_FILES['main_image']['name']);
    $targetFile = $uploadFolder . $filename;

    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetFile)) {
        $mainImagePath = '/uploads/nearby_places/' . $filename; // store relative path
    } else {
        echo json_encode(['success'=>false, 'error'=>'Failed to upload main image']);
        exit;
    }
}

if ($placeId > 0) {
    // Update existing
    $stmt = $conn->prepare("UPDATE nearby_places_main SET title=?, description=?, Maps_link=?".($mainImagePath ? ", main_image=?" : "")." WHERE id=?");
    if ($mainImagePath) {
        $stmt->bind_param("ssssi", $title, $description, $maps_link, $mainImagePath, $placeId);
    } else {
        $stmt->bind_param("sssi", $title, $description, $maps_link, $placeId);
    }
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Main details updated', 'id'=>$placeId]);
} else {
    // Insert new
    $stmt = $conn->prepare("INSERT INTO nearby_places_main (title, description, Maps_link, main_image) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $title, $description, $maps_link, $mainImagePath);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Main place added', 'id'=>$newId]);
}
?>
