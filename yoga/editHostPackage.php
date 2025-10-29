<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

if (!isset($_SESSION['yoga_host_id'])) {
    header("Location: " . YOGA_URL . "login.php");
    exit;
}

$host_id = $_SESSION['yoga_host_id'];
$success = $error = "";

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid package ID.");
}
$id = intval($_GET['id']);

// Fetch package
$pkgQ = $conn->prepare("
    SELECT p.*, r.organization_id 
    FROM yoga_packages p 
    JOIN yoga_retreats r ON p.retreat_id = r.id
    JOIN organizations o ON r.organization_id = o.id
    WHERE p.id = ? AND o.created_by = ?
    LIMIT 1
");
$pkgQ->bind_param("ii", $id, $host_id);
$pkgQ->execute();
$pkgRes = $pkgQ->get_result();
if ($pkgRes->num_rows === 0) {
    die("Package not found or access denied.");
}
$package = $pkgRes->fetch_assoc();

$org_id = $package['organization_id'];
$retreat_id = $package['retreat_id'];

// Load orgs and retreats
$organizations = $conn->query("SELECT id, name FROM organizations WHERE created_by=$host_id ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$retreats = $conn->query("SELECT id, title FROM yoga_retreats WHERE organization_id=$org_id ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);

/* =====================
   UPDATE PACKAGE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $retreat_id = intval($_POST['retreat_id']);
    $title = trim($_POST['title']);
    $slug = strtolower(str_replace(' ', '-', $title));
    $description = trim($_POST['description']);
    $price_per_person = floatval($_POST['price_per_person']);
    $min_persons = intval($_POST['min_persons']);
    $max_persons = intval($_POST['max_persons']);
    $nights = intval($_POST['nights']);
    $meals_included = isset($_POST['meals_included']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE yoga_packages 
        SET retreat_id=?, title=?, slug=?, description=?, price_per_person=?, 
            min_persons=?, max_persons=?, nights=?, meals_included=?, updated_at=NOW() 
        WHERE id=?
    ");
    $stmt->bind_param("isssdiiiii", $retreat_id, $title, $slug, $description, $price_per_person, 
                      $min_persons, $max_persons, $nights, $meals_included, $id);

    if ($stmt->execute()) {
        /* --------------------
           Update Daily Schedule
        ---------------------*/
        $conn->query("DELETE FROM yoga_package_schedule WHERE package_id=$id");
        if (!empty($_POST['schedule_time']) && !empty($_POST['schedule_activity'])) {
            $schedStmt = $conn->prepare("INSERT INTO yoga_package_schedule (package_id, time, activity) VALUES (?, ?, ?)");
            foreach ($_POST['schedule_time'] as $i => $time) {
                $activity = trim($_POST['schedule_activity'][$i]);
                if ($time && $activity) {
                    $schedStmt->bind_param("iss", $id, $time, $activity);
                    $schedStmt->execute();
                }
            }
            $schedStmt->close();
        }

        /* --------------------
           Update Accommodation + Images
        ---------------------*/
        $uploadDir = "../uploads/accommodations/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $existingAccIds = [];
        $resAcc = $conn->query("SELECT id FROM yoga_package_accommodations WHERE package_id=$id");
        while($r = $resAcc->fetch_assoc()) $existingAccIds[] = $r['id'];

        if (!empty($_POST['accommodation_type'])) {
            foreach ($_POST['accommodation_type'] as $i => $type) {
                $type = trim($type);
                $price = floatval($_POST['accommodation_price'][$i]);
                $acc_id = isset($_POST['accommodation_id'][$i]) ? intval($_POST['accommodation_id'][$i]) : 0;

                if ($type && $price > 0) {
                    if ($acc_id && in_array($acc_id, $existingAccIds)) {
                        // Update existing
                        $stmt2 = $conn->prepare("UPDATE yoga_package_accommodations SET accommodation_type=?, price_per_person=? WHERE id=?");
                        $stmt2->bind_param("sdi", $type, $price, $acc_id);
                        $stmt2->execute();
                        $stmt2->close();
                    } else {
                        // New record
                        $stmt2 = $conn->prepare("INSERT INTO yoga_package_accommodations (package_id, accommodation_type, price_per_person) VALUES (?, ?, ?)");
                        $stmt2->bind_param("isd", $id, $type, $price);
                        $stmt2->execute();
                        $acc_id = $stmt2->insert_id;
                        $stmt2->close();
                    }

                    // Handle uploaded images
                    $fieldName = "accommodation_images_new_$i";
                    if (!empty($_FILES[$fieldName]['name'][0])) {
                        foreach ($_FILES[$fieldName]['tmp_name'] as $k => $tmp) {
                            if (is_uploaded_file($tmp)) {
                                $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES[$fieldName]['name'][$k]);
                                $targetPath = $uploadDir . $filename;
                                if (move_uploaded_file($tmp, $targetPath)) {
                                    $relPath = "uploads/accommodations/" . $filename;
                                    $imgStmt = $conn->prepare("INSERT INTO yoga_accommodation_images (accommodation_id, image_path) VALUES (?, ?)");
                                    $imgStmt->bind_param("is", $acc_id, $relPath);
                                    $imgStmt->execute();
                                    $imgStmt->close();
                                }
                            }
                        }
                    }
                }
            }
        }

        // Remove accommodations not present in form
        if (!empty($existingAccIds)) {
            $formAccIds = array_filter(array_map('intval', $_POST['accommodation_id'] ?? []));
            $toDelete = array_diff($existingAccIds, $formAccIds);
            foreach ($toDelete as $delId) {
                $conn->query("DELETE FROM yoga_accommodation_images WHERE accommodation_id=$delId");
                $conn->query("DELETE FROM yoga_package_accommodations WHERE id=$delId");
            }
        }

        $success = "Package updated successfully!";
    } else {
        $error = "Failed to update package: " . $conn->error;
    }
    $stmt->close();
}

// Fetch updated data
$schedules = $conn->query("SELECT * FROM yoga_package_schedule WHERE package_id=$id ORDER BY time ASC")->fetch_all(MYSQLI_ASSOC);
$accommodations = $conn->query("SELECT * FROM yoga_package_accommodations WHERE package_id=$id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__.'/../includes/head.php'; ?>
  <title>Edit Package</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
  <style>
    .accommodation-thumb {
      position: relative;
      display: inline-block;
    }
    .accommodation-thumb button {
      position: absolute;
      top: 2px;
      right: 2px;
      background: rgba(255,0,0,0.8);
      color: white;
      border: none;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      cursor: pointer;
      font-size: 13px;
      line-height: 1;
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
      <h2>Edit Package</h2>

      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Organization</label>
          <select class="form-control" disabled>
            <?php foreach($organizations as $org): ?>
              <option <?= ($org['id']==$org_id)?'selected':'' ?>><?= htmlspecialchars($org['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Retreat</label>
          <select name="retreat_id" class="form-control" required>
            <?php foreach($retreats as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($r['id']==$retreat_id)?'selected':'' ?>><?= htmlspecialchars($r['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6"><label>Package Title</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($package['title']) ?>" required></div>
        <div class="col-md-6"><label>Price per Person (₹)</label><input type="number" step="0.01" name="price_per_person" class="form-control" value="<?= $package['price_per_person'] ?>" required></div>
        <div class="col-md-3"><label>Min Persons</label><input type="number" name="min_persons" class="form-control" value="<?= $package['min_persons'] ?>"></div>
        <div class="col-md-3"><label>Max Persons</label><input type="number" name="max_persons" class="form-control" value="<?= $package['max_persons'] ?>"></div>
        <div class="col-md-3"><label>Nights</label><input type="number" name="nights" class="form-control" value="<?= $package['nights'] ?>"></div>
        <div class="col-md-3 d-flex align-items-center">
          <div class="form-check mt-2">
            <input type="checkbox" name="meals_included" class="form-check-input" id="meals_included" <?= $package['meals_included']?'checked':'' ?>>
            <label for="meals_included" class="form-check-label">Meals Included</label>
          </div>
        </div>

        <!-- Daily Schedule -->
        <div class="col-12">
          <label>Daily Schedule</label>
          <div id="scheduleContainer">
            <?php foreach($schedules as $s): ?>
            <div class="row mb-2 schedule-row">
              <div class="col-md-3"><input type="time" name="schedule_time[]" class="form-control" value="<?= htmlspecialchars($s['time']) ?>"></div>
              <div class="col-md-7"><input type="text" name="schedule_activity[]" class="form-control" value="<?= htmlspecialchars($s['activity']) ?>"></div>
              <div class="col-md-2"><button type="button" class="btn btn-danger removeRow">Remove</button></div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-secondary mt-2" id="addRowBtn">+ Add Another</button>
        </div>

        <!-- Accommodation Section -->
        <div class="col-12">
          <label class="form-label">Accommodation Options</label>
          <div id="accommodationContainer">
            <?php foreach($accommodations as $i=>$acc): 
              $acc_id = $acc['id'];
              $imgs = $conn->query("SELECT * FROM yoga_accommodation_images WHERE accommodation_id=$acc_id")->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="accommodation-block border rounded p-3 mb-3">
              <input type="hidden" name="accommodation_id[]" value="<?= $acc['id'] ?>">
              <div class="row g-2 mb-2">
                <div class="col-md-6"><input type="text" name="accommodation_type[]" class="form-control" value="<?= htmlspecialchars($acc['accommodation_type']) ?>" required></div>
                <div class="col-md-4"><input type="number" step="0.01" name="accommodation_price[]" class="form-control" value="<?= $acc['price_per_person'] ?>" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger removeAcc">Remove</button></div>
              </div>

              <div class="mb-2">
                <label>Existing Images:</label>
                <div class="d-flex flex-wrap gap-2">
                  <?php foreach($imgs as $img): ?>
                    <div class="accommodation-thumb" id="img-<?= $img['id'] ?>">
                      <img src="../<?= htmlspecialchars($img['image_path']) ?>" width="120" height="80" style="object-fit:cover;border-radius:4px;">
                      <button type="button" onclick="deleteImage(<?= $img['id'] ?>)">×</button>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="mb-2">
                <label>Upload New Images</label>
                <input type="file" name="accommodation_images_new_<?= $i ?>[]" class="form-control" multiple accept="image/*">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-secondary mt-2" id="addAccBtn">+ Add Accommodation</button>
        </div>

        <div class="col-12">
          <label>Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($package['description']) ?></textarea>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Update Package</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Add rows
document.getElementById('addRowBtn').addEventListener('click', ()=>{
  const c=document.getElementById('scheduleContainer');
  const r=document.createElement('div');
  r.className='row mb-2 schedule-row';
  r.innerHTML=`<div class="col-md-3"><input type="time" name="schedule_time[]" class="form-control"></div>
  <div class="col-md-7"><input type="text" name="schedule_activity[]" class="form-control" placeholder="Activity"></div>
  <div class="col-md-2"><button type="button" class="btn btn-danger removeRow">Remove</button></div>`;
  c.appendChild(r);
});
document.addEventListener('click', e=>{
  if(e.target.classList.contains('removeRow')) e.target.closest('.schedule-row').remove();
  if(e.target.classList.contains('removeAcc')) e.target.closest('.accommodation-block').remove();
});
document.getElementById('addAccBtn').addEventListener('click', ()=>{
  const c=document.getElementById('accommodationContainer');
  const i=c.querySelectorAll('.accommodation-block').length;
  const b=document.createElement('div');
  b.className='accommodation-block border rounded p-3 mb-3';
  b.innerHTML=`<div class="row g-2 mb-2">
  <div class="col-md-6"><input type="text" name="accommodation_type[]" class="form-control" placeholder="Type"></div>
  <div class="col-md-4"><input type="number" step="0.01" name="accommodation_price[]" class="form-control" placeholder="Price"></div>
  <div class="col-md-2"><button type="button" class="btn btn-danger removeAcc">Remove</button></div></div>
  <div class="mb-2"><label>Upload Images</label><input type="file" name="accommodation_images_new_${i}[]" class="form-control" multiple accept="image/*"></div>`;
  c.appendChild(b);
});

// AJAX delete image
function deleteImage(imgId) {
  if(!confirm("Delete this image?")) return;
  fetch('deleteAccommodationImage.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'img_id='+imgId
  }).then(r=>r.json()).then(res=>{
    if(res.success) document.getElementById('img-'+imgId).remove();
    else alert('Failed to delete image');
  }).catch(console.error);
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
