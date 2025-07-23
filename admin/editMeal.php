<?php include 'session.php'; ?>
<?php include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allMeals.php");
    exit;
}

$id = $_GET['id'];
$query = "SELECT * FROM meal_plan WHERE id = $id";
$result = mysqli_query($conn, $query);
$meal = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);

    $updateQuery = "UPDATE meal_plan SET name='$name', description='$description', price='$price' WHERE id=$id";
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: allMeals.php");
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
                <h2>Edit Meal Plan</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Meal Name</label>
                        <input type="text" name="name" class="form-control" value="<?= $meal['name'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= $meal['description'] ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" name="price" class="form-control" value="<?= $meal['price'] ?>" step="0.01" required>
                    </div>
                    <button type="submit" name="update" class="btn btn-success">Update</button>
                    <a href="allMeals.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
