<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

if(!isset($_SESSION['yoga_host_id'])) {
    header("Location: ".YOGA_URL."login.php");
    exit;
}

$host_id = $_SESSION['yoga_host_id'];

$success = $error = "";

// Fetch host's organizations
$org_res = $conn->query("SELECT id, name FROM organizations WHERE created_by=$host_id ORDER BY name ASC");
$organizations = $org_res->fetch_all(MYSQLI_ASSOC);

// Handle POST
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

    // Check duplicate slug
    $stmt = $conn->prepare("SELECT id FROM yoga_packages WHERE slug=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Package with this title already exists.";
        }
        $stmt->close();
    }

    if (empty($error)) {
        $stmt = $conn->prepare("
            INSERT INTO yoga_packages 
            (retreat_id, title, slug, description, price_per_person, min_persons, max_persons, nights, meals_included, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        if ($stmt) {
            $stmt->bind_param("isssdiiii", $retreat_id, $title, $slug, $description, $price_per_person, $min_persons, $max_persons, $nights, $meals_included);
            if ($stmt->execute()) {
                $success = "Package created successfully!";
            } else {
                $error = "Failed to create package: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare insert statement: " . $conn->error;
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__.'/../includes/head.php'; ?>
    <title>Create Package</title>
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
            <h2>Create Package</h2>

            <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Organization</label>
                    <select name="organization_id" id="organization_id" class="form-control" required>
                        <option value="">Select Organization</option>
                        <?php foreach($organizations as $org): ?>
                            <option value="<?= $org['id'] ?>"><?= htmlspecialchars($org['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Retreat</label>
                    <select name="retreat_id" id="retreat_id" class="form-control" required>
                        <option value="">Select Retreat</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Package Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Price per Person (₹)</label>
                    <input type="number" step="0.01" name="price_per_person" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Min Persons</label>
                    <input type="number" name="min_persons" class="form-control" value="1" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Max Persons</label>
                    <input type="number" name="max_persons" class="form-control" value="1" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nights</label>
                    <input type="number" name="nights" class="form-control" value="0">
                </div>

                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="meals_included" class="form-check-input" id="meals_included" checked>
                        <label class="form-check-label" for="meals_included">Meals Included</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('organization_id').addEventListener('change', function() {
    const orgId = this.value;
    const retreatSelect = document.getElementById('retreat_id');
    retreatSelect.innerHTML = '<option value="">Loading...</option>';

    if(orgId === '') {
        retreatSelect.innerHTML = '<option value="">Select Retreat</option>';
        return;
    }

    fetch('fetchRetreatsByOrg.php?org_id='+orgId)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">Select Retreat</option>';
            data.forEach(r => {
                options += `<option value="${r.id}">${r.title}</option>`;
            });
            retreatSelect.innerHTML = options;
        })
        .catch(err => {
            console.error(err);
            retreatSelect.innerHTML = '<option value="">Error loading retreats</option>';
        });
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>
