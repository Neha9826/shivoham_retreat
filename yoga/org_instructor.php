<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

if (!isset($_SESSION['yoga_host_id'])) {
    header("Location: " . YOGA_URL . "login.php");
    exit;
}

$errors = [];
$success = '';
$host_id = $_SESSION['yoga_host_id'];

// Fetch host's organizations
$orgRes = $conn->query("SELECT * FROM organizations WHERE created_by = $host_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $bio   = mysqli_real_escape_string($conn, $_POST['bio']);
    $social_links   = mysqli_real_escape_string($conn, $_POST['social_links']);
    $organization_id = intval($_POST['organization_id']);
    $type = 'full_time'; // always full-time for host registrations

    $photo_path = NULL;
    $verification_path = NULL;

    // Upload dir
    $uploadDir = __DIR__ . '/../uploads/instructors/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Photo upload
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = 'photo_' . time() . '.' . $ext;
        $target = $uploadDir . $photo_name;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_path = 'uploads/instructors/' . $photo_name;
        } else {
            $errors[] = "Photo upload failed.";
        }
    }

    // Verification file upload
    if (!empty($_FILES['verification_file']['name'])) {
        $ext = pathinfo($_FILES['verification_file']['name'], PATHINFO_EXTENSION);
        $verif_name = 'verify_' . time() . '.' . $ext;
        $target = $uploadDir . $verif_name;
        if (move_uploaded_file($_FILES['verification_file']['tmp_name'], $target)) {
            $verification_path = 'uploads/instructors/' . $verif_name;
        } else {
            $errors[] = "Verification file upload failed.";
        }
    }

    if (!$organization_id) {
        $errors[] = "Please select an organization.";
    }

    if (empty($errors)) {
    // Always force host-created instructors to be full-time
    $type = 'full-time';

    $sql = "INSERT INTO yoga_instructors 
            (name, email, phone, bio, photo, social_links, type, organization_id, verification_file, created_at) 
            VALUES 
            ('$name','$email','$phone','$bio','$photo_path','$social_links', '$type','$organization_id','$verification_path',NOW())";

    if (mysqli_query($conn, $sql)) {
        $success = "Instructor added successfully!";
    } else {
        $errors[] = "DB Error: " . mysqli_error($conn);
    }
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <title>Add Instructor | Shivoham Retreat</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
</head>
<body class="yoga-page">
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/fixed_social_bar.php'; ?>
<?php include __DIR__ . '/yoga_navbar.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include 'host_sidebar.php'; ?>
    <div class="col-md-9 col-lg-10 p-4">
      <h2>Add Instructor</h2>

      <?php if($errors): ?>
        <div class="alert alert-danger"><?php foreach($errors as $e) echo $e."<br>"; ?></div>
      <?php endif; ?>
      <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label>Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
      <label>Phone</label>
      <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label>Organization</label>
      <select name="organization_id" class="form-select" required>
        <option value="">Select Organization</option>
        <?php while ($o = $orgRes->fetch_assoc()): ?>
          <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-md-6 mb-3">
      <label>Photo</label>
      <input type="file" name="photo" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
      <label>Verification File</label>
      <input type="file" name="verification_file" class="form-control">
    </div>

    <div class="col-md-6 mb-3">
      <label>Bio</label>
      <textarea name="bio" class="form-control" rows="3"></textarea>
    </div>
    <div class="col-md-6 mb-3">
      <label>Social Links</label>
      <textarea name="social_links" class="form-control" rows="3" placeholder="Comma-separated or JSON"></textarea>
    </div>
  </div>

  <div class="text-center">
    <button type="submit" class="btn btn-primary">Add Instructor</button>
  </div>
</form>

    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
