<?php
// admin/cancellation_policy/handler.php

include '../session.php';
include '../db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if an action is specified in the POST or GET request
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    $response = ['success' => false, 'error' => ''];

    try {
        switch ($action) {
            case 'insert':
                // Insert a new cancellation policy
                $time_period = $_POST['time_period'];
                $refundable_percentage = (int)$_POST['refundable_percentage'];
                $stmt = $conn->prepare("INSERT INTO cancellation_policy (time_period, refundable_percentage) VALUES (?, ?)");
                $stmt->bind_param("si", $time_period, $refundable_percentage);
                if ($stmt->execute()) {
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Failed to insert policy.';
                }
                $stmt->close();
                break;

            case 'update':
                // Update an existing cancellation policy
                $id = $_POST['id'];
                $time_period = $_POST['time_period'];
                $refundable_percentage = (int)$_POST['refundable_percentage'];
                $stmt = $conn->prepare("UPDATE cancellation_policy SET time_period = ?, refundable_percentage = ? WHERE id = ?");
                $stmt->bind_param("sii", $time_period, $refundable_percentage, $id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Failed to update policy.';
                }
                $stmt->close();
                break;

            case 'delete':
                // Delete a cancellation policy
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM cancellation_policy WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Failed to delete policy.';
                }
                $stmt->close();
                break;
                
            case 'get':
                // Get a single cancellation policy record for editing
                if (isset($_GET['id'])) {
                    $id = $_GET['id'];
                    $stmt = $conn->prepare("SELECT * FROM cancellation_policy WHERE id = ? LIMIT 1");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $response['success'] = true;
                        $response['row'] = $row;
                    } else {
                        $response['error'] = 'Record not found.';
                    }
                    $stmt->close();
                } else {
                    $response['error'] = 'ID not provided.';
                }
                break;

            default:
                $response['error'] = 'Invalid action.';
                break;
        }
    } catch (Exception $e) {
        $response['error'] = 'Server error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'No action specified.']);
}

$conn->close();
?>