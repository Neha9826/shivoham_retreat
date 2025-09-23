<?php
// /admin/yoga/editInstructor.php
// include '../session.php';
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid instructor ID.';
    header('Location: manageInstructors.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM yoga_instructors WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['flash_error'] = 'Instructor not found.';
    header('Location: manageInstructors.php');
    exit;
}
$instructor = $res->fetch_assoc();

$errors = [];
$name = $instructor['name'];
$bio = $instructor['bio'];
$photo = $instructor['photo'];
$social_links = $instructor['social_links'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $social_links = trim($_POST['social_links'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';

    // Photo upload
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "../../uploads/instructors/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $photo = "uploads/instructors/" . $filename;
        } else {
            $errors[] = 'Failed to upload photo.';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE yoga_instructors SET name=?, bio=?, photo=?, social_links=? WHERE id=?");
        $stmt->bind_param('ssssi', $name, $bio, $photo, $social_links, $id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Instructor updated.';
            header('Location: manageInstructors.php');
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/head.php'; ?>
<link href="../css/styles.css" rel="stylesheet">
<body class="sb-nav-fixed">
<?php include '../includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include '../includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Instructor</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="card mb-4">
                        <div class="card-header">Instructor Info</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($bio); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <input type="file" name="photo" class="form-control">
                                <?php if ($photo): ?>
                                    <div class="mt-2"><img src="../../<?= htmlspecialchars($photo); ?>" alt="" style="height:60px;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Social Links (JSON)</label>
                                <textarea name="social_links" class="form-control"><?= htmlspecialchars($social_links); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Instructor</button>
                    <a href="manageInstructors.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
