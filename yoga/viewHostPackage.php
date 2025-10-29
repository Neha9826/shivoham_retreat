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

// ✅ Fetch daily schedule
$schedRes = $conn->query("SELECT time, activity FROM yoga_package_schedule WHERE package_id=$id ORDER BY time ASC");
$schedules = $schedRes ? $schedRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__.'/../includes/head.php'; ?>
  <title>View Package | <?= htmlspecialchars($package['title']) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
  <style>
    .schedule-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
    }
    .schedule-table th, .schedule-table td {
      border: 1px solid #ddd;
      padding: 8px 12px;
    }
    .schedule-table th {
      background: #f8f9fa;
      text-align: left;
    }
    .schedule-empty {
      color: #888;
      font-style: italic;
      margin-top: 8px;
    }
  </style>
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
      <p><strong>Created:</strong> <?= htmlspecialchars($package['created_at']) ?></p>
      <p><strong>Last Updated:</strong> <?= htmlspecialchars($package['updated_at']) ?></p>

      <!-- ✅ Daily Schedule Section -->
      <h4 class="mt-4">Daily Schedule</h4>
      <?php if (!empty($schedules)): ?>
        <table class="schedule-table">
          <thead>
            <tr>
              <th style="width:150px;">Time</th>
              <th>Activity</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($schedules as $s): ?>
              <tr>
                <td><?= htmlspecialchars(date('h:i A', strtotime($s['time']))) ?></td>
                <td><?= htmlspecialchars($s['activity']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="schedule-empty">No daily schedule added for this package.</p>
      <?php endif; ?>

      <div class="mt-4">
        <a href="editHostPackage.php?id=<?= $package['id'] ?>" class="btn btn-warning">Edit</a>
        <a href="deleteHostPackage.php?id=<?= $package['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this package?')">Delete</a>
        <a href="allHostPackages.php" class="btn btn-secondary">Back</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
