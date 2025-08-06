<?php
session_start();
include 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = $_POST['email_or_phone'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header("Location: profile.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Login</title>
    <?php include 'includes/head.php'; ?>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container my-5">
    <h3 class="text-center mb-4">Login to Your Account</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="col-md-6 mx-auto border p-4 shadow rounded bg-light">
        <div class="form-group">
            <label>Email or Phone</label>
            <input type="text" name="email_or_phone" class="form-control" required>
        </div>
        <div class="form-group mt-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary mt-4 w-100">Login</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
