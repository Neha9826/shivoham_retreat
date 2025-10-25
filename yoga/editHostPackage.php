<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

if(!isset($_SESSION['yoga_host_id'])) header("Location: ".YOGA_URL."login.php");

$host_id = $_SESSION['yoga_host_id'];
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid ID");

$id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT p.*, r.title AS retreat_name, r.id AS retreat_id, o.id AS org_id, o.name AS org_name
FROM yoga_packages p
JOIN yoga_retreats r ON p.retreat_id = r.id
JOIN organizations o ON r.organization_id = o.id
WHERE p.id=? AND o.created_by=?
");
$stmt->bind_param("ii", $id, $host_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$package) die("Package not found.");

$success = $error = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $title = trim($_POST['title']);
    $slug = strtolower(str_replace(' ', '-', $title));
    $description = trim($_POST['description']);
    $price = floatval($_POST['price_per_person']);
    $min = intval($_POST['min_persons']);
    $max = intval($_POST['max_persons']);
    $nights = intval($_POST['nights']);
    $meals = isset($_POST['meals_included']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE yoga_packages 
        SET title=?, slug=?, description=?, price_per_person=?, min_persons=?, max_persons=?, nights=?, meals_included=?, updated_at=NOW()
        WHERE id=?
    ");
    $stmt->bind_param("sssdiidii", $title, $slug, $description, $price, $min, $max, $nights, $meals, $id);
    if($stmt->execute()) $success="Package updated successfully!";
    else $error="Update failed: ".$conn->error;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__.'/../includes/head.php'; ?>
  <title>Edit Package</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
</head>
<body class="yoga-page">
<?php include __DIR__.'/../includes/header.php'; ?>
<?php include __DIR__.'/../includes/fixed_social_bar.php'; ?>
<?php include __DIR__.'/yoga_navbar.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include 'host_sidebar.php'; ?>
    <div class="col-md-9 col-lg-10 p-4">
      <h2>Edit Package</h2>
      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Title</label>
          <input type="text" name="title" value="<?= htmlspecialchars($package['title']) ?>" class="form-control" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($package['description']) ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">Price per Person</label>
          <input type="number" step="0.01" name="price_per_person" class="form-control" value="<?= $package['price_per_person'] ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Min Persons</label>
          <input type="number" name="min_persons" class="form-control" value="<?= $package['min_persons'] ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Max Persons</label>
          <input type="number" name="max_persons" class="form-control" value="<?= $package['max_persons'] ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Nights</label>
          <input type="number" name="nights" class="form-control" value="<?= $package['nights'] ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Meals Included</label><br>
          <input type="checkbox" name="meals_included" <?= $package['meals_included'] ? 'checked' : '' ?>> Yes
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Update</button>
            <a href="allHostPackages.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
