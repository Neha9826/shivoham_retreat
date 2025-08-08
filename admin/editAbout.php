<?php
include 'session.php';
include 'db.php';

$section = $_GET['section'] ?? '';
$id = intval($_GET['id'] ?? 0);
if (!$section || !$id) { header("Location: allAbout.php"); exit; }

if ($section === 'main') {
    $res = mysqli_query($conn, "SELECT * FROM about_1 WHERE id = $id");
} elseif ($section === 'info') {
    $res = mysqli_query($conn, "SELECT * FROM about_info WHERE id = $id");
} elseif ($section === 'slider') {
    $res = mysqli_query($conn, "SELECT * FROM about_slider WHERE id = $id");
} else {
    echo "Invalid section.";
    exit;
}
$item = mysqli_fetch_assoc($res) ?: die("Record not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($section === 'main') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $img1 = $item['image_1']; $img2 = $item['image_2']; $up='uploads/about/'; if (!is_dir($up)) mkdir($up,0777,true);
        if ($_FILES['image_1']['error'] === UPLOAD_ERR_OK) {
          $img1 = $up . time() . '_' . basename($_FILES['image_1']['name']);
          move_uploaded_file($_FILES['image_1']['tmp_name'], $img1);
        }
        if ($_FILES['image_2']['error'] === UPLOAD_ERR_OK) {
          $img2 = $up . time() . '_' . basename($_FILES['image_2']['name']);
          move_uploaded_file($_FILES['image_2']['tmp_name'], $img2);
        }
        mysqli_query($conn, "UPDATE about_1 SET title='$title', description='$description', image_1='$img1', image_2='$img2' WHERE id=$id");

    } elseif ($section === 'info') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        mysqli_query($conn, "UPDATE about_info SET title='$title', description='$description' WHERE id=$id");

    } elseif ($section === 'slider') {
        $imagePath = $item['image_path']; $up='uploads/about_slider/'; if (!is_dir($up)) mkdir($up,0777,true);
        if ($_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
          $imagePath = $up . time() . '_' . basename($_FILES['image_path']['name']);
          move_uploaded_file($_FILES['image_path']['tmp_name'], $imagePath);
        }
        mysqli_query($conn, "UPDATE about_slider SET image_path='$imagePath' WHERE id=$id");
    }

    header("Location: allAbout.php");
    exit;
}
?>
<!DOCTYPE html><html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav"><?php include 'includes/sidebar.php'; ?><div id="layoutSidenav_content">
<main class="container-fluid px-4 mt-4">
  <h2>Edit <?= ucfirst($section) ?></h2>
  <form method="POST" enctype="multipart/form-data">
    <?php if ($section==='main'): ?>
      <div class="mb-3"><label>Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required></div>
      <div class="mb-3"><label>Description</label>
        <textarea name="description" id="desc_field" class="form-control"><?= htmlspecialchars($item['description']) ?></textarea></div>
      <div class="mb-3"><label>Image 1</label><br>
        <?php if ($item['image_1']): ?><img src="<?= $item['image_1'] ?>" width="100"><br><?php endif; ?>
        <input type="file" name="image_1" class="form-control"></div>
      <div class="mb-3"><label>Image 2</label><br>
        <?php if ($item['image_2']): ?><img src="<?= $item['image_2'] ?>" width="100"><br><?php endif; ?>
        <input type="file" name="image_2" class="form-control"></div>
    <?php elseif ($section==='info'): ?>
      <div class="mb-3"><label>Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title']) ?>" required></div>
      <div class="mb-3"><label>Description</label>
        <textarea name="description" id="desc_field" class="form-control"><?= htmlspecialchars($item['description']) ?></textarea></div>
    <?php elseif ($section==='slider'): ?>
      <div class="mb-3"><label>Slider Image</label><br>
        <img src="<?= $item['image_path'] ?>" width="150"><br>
        <input type="file" name="image_path" class="form-control mt-2"></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="allAbout.php" class="btn btn-secondary">Cancel</a>
  </form>
</main></div></div>

<script src="https://cdn.ckeditor.com/4.25.1/standard-all/ckeditor.js"></script>
<script>
<?php if (in_array($section, ['main', 'info'])): ?>
  CKEDITOR.replace('desc_field');
<?php endif; ?>
</script>
<?php include 'includes/script.php'; ?>
</body></html>
