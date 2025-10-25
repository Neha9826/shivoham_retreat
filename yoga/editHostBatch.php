<?php
session_start();
include __DIR__.'/../config.php';
include __DIR__.'/../db.php';

if(!isset($_SESSION['yoga_host_id'])) header("Location: ".YOGA_URL."login.php");
$host_id = $_SESSION['yoga_host_id'];

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid batch ID.");
$batch_id = intval($_GET['id']);
$success = $error = '';

// Fetch batch and verify host ownership
$stmt = $conn->prepare("
SELECT b.*, p.id AS package_id, p.title AS package_title, r.id AS retreat_id, r.organization_id
FROM yoga_batches b
JOIN yoga_packages p ON b.package_id=p.id
JOIN yoga_retreats r ON p.retreat_id=r.id
JOIN organizations o ON r.organization_id=o.id
WHERE b.id=? AND o.created_by=?
");
$stmt->bind_param("ii",$batch_id,$host_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$batch) die("Batch not found.");

// Fetch organizations for dropdown
$org_res = $conn->query("SELECT * FROM organizations WHERE created_by=$host_id ORDER BY name ASC");

// Handle POST
if($_SERVER['REQUEST_METHOD']==='POST'){
    $package_id = intval($_POST['package_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $capacity = intval($_POST['capacity'] ?? 0);
    $price_override = $_POST['price_override'] ?: null;
    $notes = $_POST['notes'] ?? '';

    if(!$package_id || !$start_date || !$end_date || $capacity <= 0){
        $error = "Please fill all required fields correctly.";
    } else {
        // Check overlapping batches (exclude current batch)
        $stmt = $conn->prepare("SELECT id FROM yoga_batches WHERE package_id=? AND id!=? AND ((start_date<=? AND end_date>=?) OR (start_date<=? AND end_date>=?)) LIMIT 1");
        $stmt->bind_param("iissss", $package_id, $batch_id, $start_date, $start_date, $end_date, $end_date);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0){
            $error = "Another batch for this package overlaps with selected dates.";
        } else {
            $stmt->close();

            // Adjust available_slots if capacity changed
            $slots_diff = $capacity - $batch['capacity'];
            $new_available = $batch['available_slots'] + $slots_diff;
            if($new_available < 0) $new_available = 0; // don't allow negative slots

            $stmt = $conn->prepare("
                UPDATE yoga_batches
                SET package_id=?, start_date=?, end_date=?, capacity=?, available_slots=?, price_override=?, notes=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->bind_param("iissidsi", $package_id, $start_date, $end_date, $capacity, $new_available, $price_override, $notes, $batch_id);
            if($stmt->execute()) $success = "Batch updated successfully!";
            else $error = "Update failed: ".$conn->error;
            $stmt->close();

            // Refresh batch data for form
            $stmt = $conn->prepare("SELECT * FROM yoga_batches WHERE id=?");
            $stmt->bind_param("i",$batch_id);
            $stmt->execute();
            $batch = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__.'/../includes/head.php'; ?>
<title>Edit Batch | <?= htmlspecialchars($batch['package_title']) ?></title>
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
<h2>Edit Batch</h2>

<?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<form method="post" class="row g-3">
    <div class="col-md-5">
        <label class="form-label">Organization</label>
        <select id="org_select" class="form-select" required>
            <option value="">Select Organization</option>
            <?php while($org=$org_res->fetch_assoc()): ?>
                <option value="<?= $org['id'] ?>" <?= $batch['organization_id']==$org['id'] ? 'selected':'' ?>><?= htmlspecialchars($org['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-5">
        <label class="form-label">Package</label>
        <select name="package_id" id="package_select" class="form-select" required>
            <option value="<?= $batch['package_id'] ?>"><?= htmlspecialchars($batch['package_title']) ?></option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= $batch['start_date'] ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= $batch['end_date'] ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Capacity</label>
        <input type="number" name="capacity" class="form-control" min="1" value="<?= $batch['capacity'] ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Price Override (optional)</label>
        <input type="number" name="price_override" class="form-control" step="0.01" value="<?= $batch['price_override'] ?>">
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars($batch['notes']) ?></textarea>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">Update Batch</button>
        <a href="viewHostBatch.php?id=<?= $batch_id ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>
</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#org_select').change(function(){
    let org_id = $(this).val();
    $('#package_select').html('<option>Loading...</option>');
    if(!org_id) return $('#package_select').html('<option value="">Select Package</option>');
    $.post('getPackages.php', {org_id: org_id}, function(res){
        $('#package_select').html(res);
    });
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
