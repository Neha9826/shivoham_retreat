<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_POST['submit'])) {
    $emp_name = mysqli_real_escape_string($conn, $_POST['emp_name']);
    $emp_email = mysqli_real_escape_string($conn, $_POST['emp_email']);
    $emp_phone = mysqli_real_escape_string($conn, $_POST['emp_phone']);
    $emp_password = mysqli_real_escape_string($conn, $_POST['emp_password']);
    $hashed_password = password_hash($emp_password, PASSWORD_DEFAULT);

    $insert = "INSERT INTO emp (name, email, phone, password) VALUES ('$emp_name', '$emp_email', '$emp_phone', '$hashed_password')";
    if (mysqli_query($conn, $insert)) {
        header("Location: allEmp.php");
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
                <h2>Add New Employee</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="emp_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="emp_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="emp_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="emp_password" class="form-control" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Add Employee</button>
                    <a href="allEmp.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
