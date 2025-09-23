<?php
// /admin/yoga/createInstructor.php
include '../db.php';
session_start();

// ensure host login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'host') {
    die("Unauthorized");
}
$host_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = $_POST['name'] ?? '';
    $bio   = $_POST['bio'] ?? '';
    $org_id = $_POST['organization_id'] ?? null;
    $photo = null;
    $verification_file = null;

    // Uploads
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "../../uploads/instructors/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $photo = $targetDir . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    if (!empty($_FILES['verification_file']['name'])) {
        $targetDir = "../../uploads/instructors/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $verification_file = $targetDir . time() . "_" . basename($_FILES['verification_file']['name']);
        move_uploaded_file($_FILES['verification_file']['tmp_name'], $verification_file);
    }

    // Insert instructor
    $stmt = $conn->prepare("INSERT INTO yoga_instructors (name, bio, photo, type, organization_id, verification_file, status, created_at) VALUES (?, ?, ?, 'full-time', ?, ?, 'pending', NOW())");
    $stmt->bind_param("ssiss", $name, $bio, $photo, $org_id, $verification_file);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Instructor created successfully! Awaiting admin approval.";
    } else {
        $_SESSION['flash_error'] = "Error: " . $stmt->error;
    }
    $stmt->close();

    header("Location: manageInstructors.php");
    exit;
}

// Fetch host’s organizations
$orgs = $conn->query("SELECT id,name FROM organizations WHERE created_by='$host_id'");
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include '../includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include '../includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container mt-4">
            <h2>Create Instructor (Full-Time)</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Bio</label>
                    <textarea name="bio" class="form-control" required></textarea>
                </div>
                <div class="mb-3">
                    <label>Photo</label>
                    <input type="file" name="photo" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Organization</label>
                    <select name="organization_id" class="form-control" required>
                        <option value="">-- Select Organization --</option>
                        <?php while ($row = $orgs->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>PAN/Aadhaar</label>
                    <input type="file" name="verification_file" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Create Instructor</button>
            </form>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
