<?php
include 'session.php';
include 'db.php';

$about1 = mysqli_query($conn, "SELECT * FROM about_1 ORDER BY id DESC");
$aboutInfo = mysqli_query($conn, "SELECT * FROM about_info ORDER BY id DESC");
$aboutSlider = mysqli_query($conn, "SELECT * FROM about_slider ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
  <?php include 'includes/sidebar.php'; ?>
  <div id="layoutSidenav_content">
    <main class="container-fluid px-4 mt-4">

      <h2>About Section Overview</h2>

      <!-- Main About Section -->
      <h4 class="mt-4">Main About Section</h4>
      <div class="table-responsive mb-4">
        <table class="table table-bordered">
          <thead>
            <tr><th>ID</th><th>Heading</th><th>Description</th><th>Images</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($about1)): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['main_heading']) ?></td>
              <td><?= htmlspecialchars(substr($row['main_description'], 0, 100)) ?>…</td>
              <td>
                <?php if (!empty($row['main_image1'])): ?>
                  <img src="<?= htmlspecialchars($row['main_image1']) ?>" width="60">
                <?php endif; ?>
                <?php if (!empty($row['main_image2'])): ?>
                  <img src="<?= htmlspecialchars($row['main_image2']) ?>" width="60">
                <?php endif; ?>
              </td>
              <td>
                <a href="editAbout.php?section=main&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($about1) === 0): ?>
            <tr><td colspan="5" class="text-center">No records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Info Items -->
      <h4>About Info Entries</h4>
      <div class="table-responsive mb-4">
        <table class="table table-bordered">
          <thead>
            <tr><th>ID</th><th>Title</th><th>Description</th><th>Image</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($aboutInfo)): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['info_title']) ?></td>
              <td><?= htmlspecialchars(substr($row['info_description'], 0, 60)) ?>…</td>
              <td>
                <?php if (!empty($row['image'])): ?>
                  <img src="<?= htmlspecialchars($row['image']) ?>" width="60">
                <?php endif; ?>
              </td>
              <td>
                <a href="editAbout.php?section=info&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($aboutInfo) === 0): ?>
            <tr><td colspan="5" class="text-center">No records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Slider Images -->
      <h4>Slider Images</h4>
      <div class="d-flex flex-wrap gap-3 mb-4">
        <?php while ($img = mysqli_fetch_assoc($aboutSlider)): ?>
          <div class="text-center">
            <img src="<?= htmlspecialchars($img['image']) ?>" alt="Slider Image" style="width:200px;height:150px;object-fit:cover;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.2);">
            <div class="mt-2">
              <a href="editAbout.php?section=slider&id=<?= $img['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            </div>
          </div>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($aboutSlider) === 0): ?>
          <p class="text-muted">No slider images found.</p>
        <?php endif; ?>
      </div>

    </main>
    <?php include 'includes/footer.php'; ?>
  </div>
</div>
<?php include 'includes/script.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
