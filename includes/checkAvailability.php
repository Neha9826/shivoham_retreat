<?php
include '../db.php';

$message = "";
$color = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = $_POST['name'];
    $checkin_date  = $_POST['check_in'];
    $checkout_date = $_POST['check_out'];
    $adults        = $_POST['adults'];
    $children      = $_POST['children'];
    $room_id       = $_POST['room_id'] ?? null;
    $email         = $_POST['email'];
    $phone         = $_POST['phone'];
    $message       = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO booking_requests (name, check_in, check_out, adults, children, room_id, email, phone, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisssss", $name, $checkin_date, $checkout_date, $adults, $children, $room_id, $email, $phone, $message);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $message = "Your query has been submitted. Our agent will contact you soon.";
        $color = "#d4edda"; // light green
        $textColor = "#155724";
        $borderColor = "#c3e6cb";
    } else {
        $message = "Something went wrong. Please try again.";
        $color = "#f8d7da"; // light red
        $textColor = "#721c24";
        $borderColor = "#f5c6cb";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            margin: 0;
            padding: 0;
        }
        .message-box {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            border: 1px solid <?= $borderColor ?>;
            background-color: <?= $color ?>;
            color: <?= $textColor ?>;
            font-size: 18px;
        }
        .back-button {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <?= $message ?>
        <br>
        <a href="../index.php" class="back-button">Back to Form</a>
    </div>
</body>
</html>
