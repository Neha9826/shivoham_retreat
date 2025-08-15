<?php
include 'db.php';

try {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$phone || !$email || !$message) {
        throw new Exception("Please fill all required fields.");
    }

    $stmt = $conn->prepare("
        INSERT INTO contact_messages (name, email, phone, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssss", $name, $email, $phone, $message);
    $stmt->execute();

    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(["success" => true, "message" => "Your message has been sent successfully!"]);
        exit;
    }

    // Normal form submission - redirect
    header("Location: contact.php?success=1");
    exit;

} catch (Exception $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit;
    }

    header("Location: contact.php?error=" . urlencode($e->getMessage()));
    exit;
}
