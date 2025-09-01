<?php
// send_otp.php
include 'db.php';
header('Content-Type: application/json');

// Include PHPMailer library files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Path to the PHPMailer files. Ensure this path is correct.
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        echo json_encode($response);
        exit;
    }

    // Check if the email is already registered
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['message'] = "This email is already registered. Please log in.";
        echo json_encode($response);
        exit;
    }
    $stmt->close();

    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // OTP valid for 5 minutes

    // Store or update the OTP in the database
    $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = VALUES(otp), expires_at = VALUES(expires_at)");
    $stmt->bind_param("sss", $email, $otp, $expires_at);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            // Server settings for Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your-email@gmail.com'; // ⚠️ REPLACE with your Gmail address
            $mail->Password   = 'your-gmail-app-password'; // ⚠️ REPLACE with your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Recipients
            $mail->setFrom('no-reply@yourwebsite.com', 'Your Website Name');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Registration';
            $mail->Body    = '
                <html>
                <body>
                    <p>Dear User,</p>
                    <p>Your One-Time Password (OTP) for registration is:</p>
                    <h2 style="color: #007bff; text-align: center;">' . $otp . '</h2>
                    <p>This OTP is valid for 5 minutes.</p>
                    <p>If you did not request this, please ignore this email.</p>
                </body>
                </html>
            ';

            $mail->send();
            $response['success'] = true;
            $response['message'] = "An OTP has been sent to your email. Please check your inbox and spam folder.";
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            $response['message'] = "Failed to send the OTP email. Please try again later.";
        }
    } else {
        $response['message'] = "Failed to generate OTP. Please try again.";
    }

    $stmt->close();
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
$conn->close();
