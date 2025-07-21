<?php
include 'db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $name = $first . " " . $last;
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $response['message'] = "Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT * FROM emp WHERE email = ? OR phone = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = "Email or phone already registered!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO emp (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Account created successfully!";
            } else {
                $response['message'] = "Error: " . $conn->error;
            }
        }
    }
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
?>
