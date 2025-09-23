<?php
include '../../session.php';
include '../../db.php';
header('Content-Type: application/json; charset=utf-8');

$placeId = isset($_POST['nearby_place_id']) ? intval($_POST['nearby_place_id']) : 0;
$sectionId = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
$heading = $_POST['side_heading'] ?? '';
$desc = $_POST['description'] ?? '';

if ($placeId == 0 || empty($heading)) {
    echo json_encode(['success'=>false,'error'=>'Place ID and heading required']); exit;
}

if ($sectionId > 0) {
    $stmt = $conn->prepare("UPDATE nearby_places_sections SET side_heading=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $heading, $desc, $sectionId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true,'message'=>'Section updated']);
} else {
    $stmt = $conn->prepare("INSERT INTO nearby_places_sections (nearby_place_id, side_heading, description) VALUES (?,?,?)");
    $stmt->bind_param("iss", $placeId, $heading, $desc);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['success'=>true,'message'=>'Section added','id'=>$newId]);
}
?>
