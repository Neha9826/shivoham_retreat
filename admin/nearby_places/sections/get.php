<?php
include '../../db.php';
include '../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'sections' => []];

try {
    if (isset($_GET['place_id'])) {
        $placeId = intval($_GET['place_id']);

        $sectionsQuery = $conn->prepare("SELECT * FROM nearby_places_sections WHERE nearby_place_id = ? ORDER BY id ASC");
        $sectionsQuery->bind_param("i", $placeId);
        $sectionsQuery->execute();
        $sectionsResult = $sectionsQuery->get_result();

        while ($section = $sectionsResult->fetch_assoc()) {
            $imagesQuery = $conn->prepare("SELECT * FROM nearby_places_images WHERE nearby_place_section_id = ? ORDER BY id ASC");
            $imagesQuery->bind_param("i", $section['id']);
            $imagesQuery->execute();
            $imagesResult = $imagesQuery->get_result();

            $images = [];
            while ($img = $imagesResult->fetch_assoc()) {
                $img['image_path_full'] = build_image_url($img['image_path']);
                $images[] = $img;
            }
            $imagesQuery->close();

            $section['images'] = $images;
            $response['sections'][] = $section;
        }
        $sectionsQuery->close();

        $response['success'] = true;
    } elseif (isset($_GET['section_id'])) {
        $sectionId = intval($_GET['section_id']);

        $stmt = $conn->prepare("SELECT * FROM nearby_places_sections WHERE id = ?");
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($section = $result->fetch_assoc()) {
            $section['images'] = [];

            $imagesQuery = $conn->prepare("SELECT * FROM nearby_places_images WHERE nearby_place_section_id = ? ORDER BY id ASC");
            $imagesQuery->bind_param("i", $sectionId);
            $imagesQuery->execute();
            $imagesResult = $imagesQuery->get_result();

            while ($img = $imagesResult->fetch_assoc()) {
                $img['image_path_full'] = build_image_url($img['image_path']);
                $section['images'][] = $img;
            }
            $imagesQuery->close();

            $response['success'] = true;
            $response['data'] = $section;
        } else {
            $response['error'] = 'Section not found';
        }
        $stmt->close();
    } else {
        $response['error'] = 'Missing parameters';
    }
} catch (Throwable $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
