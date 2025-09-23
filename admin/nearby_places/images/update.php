<?php
include '../../session.php';
include '../../db.php';
header('Content-Type: application/json; charset=utf-8');

$imageId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($imageId == 0 || empty($_FILES['image']['name'])) {
    echo json_encode(['success'=>false,'error'=>'Image ID or file missing']); exit;
}

// Upload
$uploadFolder = $_SERVER['DOCUMENT_ROOT'].'/uploads/nearby_places/sections/';
if (!file_exists($uploadFolder)) mkdir($uploadFolder, 0777, true);

$filename = time().'_'.basename($_FILES['image']['name']);
$targetFile = $uploadFolder . $filename;

if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
    $imagePath = '/uploads/nearby_places/sections/' . $filename;

    // Update DB
    $stmt = $conn->prepare("UPDATE nearby_places_section_images SET image_path=? WHERE id=?");
    $stmt->bind_param("si", $imagePath, $imageId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success'=>true,'message'=>'Image updated','path'=>$imagePath]);
} else {
    echo json_encode(['success'=>false,'error'=>'Failed to upload image']);
}
?>
