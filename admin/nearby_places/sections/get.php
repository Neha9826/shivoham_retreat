<?php
// admin/nearby_places/sections/get.php
include '../../session.php';
include '../../db.php';
include '../../includes/helpers.php'; // Corrected include path to helpers.php

header('Content-Type: application/json');

$response = ['success' => false, 'sections' => [], 'error' => ''];

if (isset($_GET['place_id']) && is_numeric($_GET['place_id'])) {
    $placeId = intval($_GET['place_id']);
    
    $sectionsQuery = mysqli_query($conn, "SELECT * FROM nearby_places_sections WHERE nearby_place_id = $placeId ORDER BY id ASC");
    
    if (!$sectionsQuery) {
        $response['error'] = 'Database query failed: ' . mysqli_error($conn);
    } else {
        $sections = [];
        while ($sectionRow = mysqli_fetch_assoc($sectionsQuery)) {
            $sectionId = $sectionRow['id'];
            
            $imagesQuery = mysqli_query($conn, "SELECT * FROM nearby_places_images WHERE nearby_place_section_id = $sectionId");
            $images = [];
            while ($imageRow = mysqli_fetch_assoc($imagesQuery)) {
                $imageRow['image_path_full'] = resolve_admin_image_url($imageRow['image_path']);
                $images[] = $imageRow;
            }
            $sectionRow['images'] = $images;
            $sections[] = $sectionRow;
        }
        $response['success'] = true;
        $response['sections'] = $sections;
    }

} else if (isset($_GET['section_id']) && is_numeric($_GET['section_id'])) {
    $sectionId = intval($_GET['section_id']);
    $result = mysqli_query($conn, "SELECT * FROM nearby_places_sections WHERE id = $sectionId");
    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['data'] = $row;
    } else {
        $response['success'] = false;
        $response['error'] = 'Section not found.';
    }

} else {
    $response['error'] = 'Invalid place ID or section ID.';
}

echo json_encode($response);
?>
