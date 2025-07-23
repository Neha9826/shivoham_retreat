<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_POST['submit'])) {
    $age_group = mysqli_real_escape_string($conn, $_POST['age_group']);
    $extra_price = floatval($_POST['extra_price']);

    $insertQuery = "INSERT INTO extra_bed_rates (age_group, extra_price) VALUES ('$age_group', '$extra_price')";
    if (mysqli_query($conn, $insertQuery)) {
        header("Location: extraBedList.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
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
                <h2>Add Extra Bed Rate</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Age Group</label>
                        <input type="text" name="age_group" class="form-control" placeholder="e.g. 0-5 years" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Extra Price (₹)</label>
                        <input type="number" name="extra_price" class="form-control" step="0.01" placeholder="Enter extra price" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Add</button>
                    <a href="allExtraBeds.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
