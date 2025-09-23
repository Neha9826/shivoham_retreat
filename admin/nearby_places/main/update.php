<?php
// admin/nearby_places/main/update.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $placeId = isset($_POST['place_id']) ? intval($_POST['place_id']) : 0;

    try {
        if ($placeId <= 0) {
            throw new Exception('Invalid Place ID.');
        }

        // Fetch current image path
        $stmt = $conn->prepare("SELECT main_image FROM nearby_places_main WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $placeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentImagePath = $result->fetch_assoc()['main_image'] ?? null;
        $stmt->close();

        $title       = $_POST['title'] ?? '';
        $mapsLink    = $_POST['google_maps_link'] ?? '';
        $description = $_POST['description'] ?? '';

        $mainImagePath = $currentImagePath; // default to current

        // === Main image upload logic (section-style) ===
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../uploads/nearby_places/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName    = time() . '_' . basename($_FILES['main_image']['name']);
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                // Delete old main image if exists
                if (!empty($currentImagePath)) {
                    $oldFile = __DIR__ . '/../../../' . $currentImagePath;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $mainImagePath = 'uploads/nearby_places/' . $fileName;
            } else {
                throw new Exception('Failed to move uploaded main image.');
            }
        } elseif (!empty($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            if (!empty($currentImagePath)) {
                $oldFile = __DIR__ . '/../../../' . $currentImagePath;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $mainImagePath = null;
        }

        // === Update record ===
        $sql = "UPDATE nearby_places_main
                   SET title = ?, Maps_link = ?, description = ?, main_image = ?
                 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $title, $mapsLink, $description, $mainImagePath, $placeId);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Nearby place updated successfully.'];
        } else {
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

echo json_encode($response);
