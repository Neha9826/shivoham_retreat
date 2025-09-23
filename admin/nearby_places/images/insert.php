<?php
// nearby_places/images/insert.php

include '../../session.php';
include '../../db.php';

// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Check section ID
$sectionId = $_POST['nearby_place_section_id'] ?? 0;
if (empty($sectionId) || !is_numeric($sectionId)) {
    echo json_encode(['success' => false, 'error' => 'Section ID missing or invalid']);
    exit;
}

// Check files
if (!isset($_FILES['images'])) {
    echo json_encode(['success' => false, 'error' => 'No files uploaded']);
    exit;
}

$files = $_FILES['images'];
$uploadedPaths = [];

// Upload folder
$uploadFolder = $_SERVER['DOCUMENT_ROOT'].'/uploads/nearby_places/sections/';
if (!is_dir($uploadFolder)) mkdir($uploadFolder, 0777, true);

// Normalize files to array
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    // Single or multiple file handling
    $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];

    if (empty($fileName) || empty($tmpName)) continue;

    // Sanitize filename
    $fileNameClean = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName));
    $targetFile = $uploadFolder . $fileNameClean;

    if (move_uploaded_file($tmpName, $targetFile)) {
        $imagePath = '/uploads/nearby_places/sections/' . $fileNameClean;

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO nearby_places_images (nearby_place_section_id, image_path) VALUES (?, ?)");
        $stmt->bind_param("is", $sectionId, $imagePath);
        $stmt->execute();
        $uploadedPaths[] = $imagePath;
    } else {
        // If any file fails
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file: '.$fileName]);
        exit;
    }
}

if (count($uploadedPaths) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Images uploaded successfully',
        'paths' => $uploadedPaths
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No valid files to upload']);
}
?>
