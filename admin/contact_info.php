<?php
include 'session.php';
include 'db.php';

// Fetch current contact info
$result = $conn->query("SELECT * FROM contact_info LIMIT 1");
$contact = $result->fetch_assoc() ?: ['address'=>'','phone'=>'','email'=>'','map_embed'=>''];

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $map     = trim($_POST['map_embed'] ?? '');

    if ($contact && $contact['id']) {
        $stmt = $conn->prepare("UPDATE contact_info SET address=?, phone=?, email=?, map_embed=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssi", $address, $phone, $email, $map, $contact['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_info (address, phone, email, map_embed) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $address, $phone, $email, $map);
    }
    $stmt->execute();
    header("Location: contact_info.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container mt-4">
            <h2>Contact Information</h2>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Updated successfully!</div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="address" class="form-control" required><?= htmlspecialchars($contact['address']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($contact['phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($contact['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Google Map Embed Code</label>
                    <textarea name="map_embed" class="form-control"><?= htmlspecialchars($contact['map_embed']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </main>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
