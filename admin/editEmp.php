<?php include 'session.php'; ?>
<?php include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: allEmp.php");
    exit;
}

$emp_id = intval($_GET['id']);

// Handle update
if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "UPDATE emp SET name='$name', phone='$phone', email='$email', role='$role', password='$password' WHERE id=$emp_id";
    if (mysqli_query($conn, $query)) {
        header("Location: allEmp.php");
        exit;
    } else {
        $error = "Error updating employee: " . mysqli_error($conn);
    }
}

// Fetch existing data
$result = mysqli_query($conn, "SELECT * FROM emp WHERE id=$emp_id");
$employee = mysqli_fetch_assoc($result);
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
                <h2>Edit Employee</h2>
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= $employee['name'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= $employee['phone'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $employee['email'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?= $employee['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="employee" <?= $employee['role'] == 'employee' ? 'selected' : '' ?>>Employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" value="<?= $employee['password'] ?>" required>
                    </div>
                    <button type="submit" name="update" class="btn btn-primary">Update</button>
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
