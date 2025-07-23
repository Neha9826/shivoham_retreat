<?php include 'session.php'; ?>
<?php include 'db.php'; ?>

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
                <h2>All Employees</h2>
                <a href="addEmp.php" class="btn btn-success mb-3">+ Add Employee</a>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $empQuery = mysqli_query($conn, "SELECT * FROM emp");
                        while ($row = mysqli_fetch_assoc($empQuery)) {
                            echo "<tr>
                                <td>{$i}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['phone']}</td>
                                <td>{$row['role']}</td>
                                <td>
                                    <a href='editEmp.php?id={$row['id']}' class='btn btn-sm btn-primary'>Edit</a>
                                    <a href='deleteEmp.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this employee?');\">Delete</a>
                                </td>
                            </tr>";
                            $i++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
