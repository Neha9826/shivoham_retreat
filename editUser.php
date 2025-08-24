<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Sanitize inputs
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$profileImagePath = null;

if ($name === '' || $phone === '') {
    $_SESSION['profile_error'] = "Name and phone number cannot be empty.";
    header("Location: profile.php");
    exit;
}

// Start building the query
$query = "UPDATE users SET name = ?, phone = ?";
$params = [$name, $phone];
$types = "ss";

// ✅ Add password update if provided
if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query .= ", password = ?";
    $params[] = $hashedPassword;
    $types .= "s";
}

// ✅ Add profile image if uploaded (base64)
$imgData = trim($_POST['cropped_image'] ?? '');
if (!empty($imgData)) {
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = base64_decode($imgData);

    $uploadDir = 'uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = $uploadDir . 'user_' . $user_id . '_' . time() . '.png';
    file_put_contents($filePath, $imgData);

    $query .= ", profile_image = ?";
    $params[] = $filePath;
    $types .= "s";
    
    // Check for old profile image and delete it to save space
    $oldUserStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $oldUserStmt->bind_param("i", $user_id);
    $oldUserStmt->execute();
    $oldUserResult = $oldUserStmt->get_result();
    $oldUser = $oldUserResult->fetch_assoc();
    $oldUserStmt->close();

    if ($oldUser && !empty($oldUser['profile_image']) && file_exists($oldUser['profile_image']) && strpos($oldUser['profile_image'], 'default') === false) {
        unlink($oldUser['profile_image']);
    }
}

// Finalize query
$query .= " WHERE id = ?";
$params[] = $user_id;
$types .= "i";

// Execute
$updateStmt = $conn->prepare($query);
$updateStmt->bind_param($types, ...$params);

if ($updateStmt->execute()) {
    $_SESSION['user_name'] = $name;
    $_SESSION['profile_updated'] = true;
} else {
    // Optional: Log or display error
    // $_SESSION['profile_error'] = "Failed to update profile: " . $updateStmt->error;
}

$updateStmt->close();
$conn->close();

// Redirect back to profile
header("Location: profile.php");
exit;
?>