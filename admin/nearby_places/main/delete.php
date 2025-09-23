<?php
// admin/nearby_places/main/delete.php
include '../../session.php';
include '../../db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $place_id = intval($_GET['id']);

    // Start a transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // First, get the main image path to delete the physical file
        $sql = "SELECT main_image FROM nearby_places_main WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $place_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $imagePath = $row['main_image'] ?? null;
        $stmt->close();

        // Delete the main image file if it exists
        if ($imagePath) {
            $fullPath = '../../' . $imagePath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Delete related sections and images.
        // Assumes ON DELETE CASCADE is not set. If it is, these lines are redundant but safe.
        mysqli_query($conn, "DELETE FROM nearby_places_images WHERE nearby_place_section_id IN (SELECT id FROM nearby_places_sections WHERE nearby_place_id = $place_id)");
        mysqli_query($conn, "DELETE FROM nearby_places_sections WHERE nearby_place_id = $place_id");
        
        // Delete the main place entry
        $sql = "DELETE FROM nearby_places_main WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $place_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Nearby place and all related data deleted successfully.'];
        } else {
            mysqli_rollback($conn);
            $response = ['success' => false, 'message' => 'No place found with that ID or deletion failed.'];
        }

        $stmt->close();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>