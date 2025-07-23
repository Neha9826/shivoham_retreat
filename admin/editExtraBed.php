<?php
include 'session.php';
include 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: extraBedList.php");
    exit();
}

$id = $_GET['id'];

// Fetch existing data
$sql = "SELECT * FROM extra_bed_rates WHERE id = $id";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Invalid ID!";
    exit();
}

// Update on form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $age_group = mysqli_real_escape_string($conn, $_POST['age_group']);
    $extra_price = mysqli_real_escape_string($conn, $_POST['extra_price']);

    $update = "UPDATE extra_bed_rates SET age_group='$age_group', extra_price='$extra_price' WHERE id=$id";
    if (mysqli_query($conn, $update)) {
        header("Location: extraBedList.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Extra Bed Rate</h2>
                <form method="POST" class="mt-4">
                    <div class="mb-3">
                        <label>Age Group</label>
                        <input type="text" name="age_group" class="form-control" required value="<?= htmlspecialchars($data['age_group']) ?>">
                    </div>
                    <div class="mb-3">
                        <label>Extra Price (₹)</label>
                        <input type="number" name="extra_price" class="form-control" required value="<?= htmlspecialchars($data['extra_price']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="extraBedList.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
