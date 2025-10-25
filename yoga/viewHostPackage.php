<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

if(!isset($_SESSION['yoga_host_id'])) header("Location: ".YOGA_URL."login.php");

$host_id = $_SESSION['yoga_host_id'];
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid ID");

$id = intval($_GET['id']);

$sql = "
SELECT p.*, r.title AS retreat_name, o.name AS org_name
FROM yoga_packages p
JOIN yoga_retreats r ON p.retreat_id = r.id
JOIN organizations o ON r.organization_id = o.id
WHERE p.id=$id AND o.created_by=$host_id
LIMIT 1
";
$res = $conn->query($sql);
$package = $res->fetch_assoc();
if(!$package) die("Package not found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__.'/../includes/head.php'; ?>
  <title>View Package | <?= htmlspecialchars($package['title']) ?></title>
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
      <h2><?= htmlspecialchars($package['title']) ?></h2>
      <p><strong>Organization:</strong> <?= htmlspecialchars($package['org_name']) ?></p>
      <p><strong>Retreat:</strong> <?= htmlspecialchars($package['retreat_name']) ?></p>
      <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($package['description'])) ?></p>
      <p><strong>Price per person:</strong> ₹<?= number_format($package['price_per_person'],2) ?></p>
      <p><strong>Persons:</strong> <?= $package['min_persons'] ?> - <?= $package['max_persons'] ?></p>
      <p><strong>Nights:</strong> <?= $package['nights'] ?></p>
      <p><strong>Meals included:</strong> <?= $package['meals_included'] ? 'Yes' : 'No' ?></p>

      <a href="editHostPackage.php?id=<?= $package['id'] ?>" class="btn btn-warning">Edit</a>
        <a href="deleteHostPackage.php?id=<?= $package['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this package?')">Delete</a>
      <a href="allHostPackages.php" class="btn btn-secondary">Back</a>
    </div>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
