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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Extra Bed Rates</h2>
                    <a href="addExtraBed.php" class="btn btn-success">Add Extra Bed Rate</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Age Group</th>
                                <th>Extra Price (₹)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sql = "SELECT * FROM extra_bed_rates ORDER BY id DESC";
                        $result = mysqli_query($conn, $sql);
                        $i = 1;
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                        <td>{$i}</td>
                                        <td>{$row['age_group']}</td>
                                        <td>₹ {$row['extra_price']}</td>
                                        <td>
                                            <a href='editExtraBed.php?id={$row['id']}' class='btn btn-sm btn-primary'>Edit</a>
                                            <a href='deleteExtraBed.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this?')\">Delete</a>
                                        </td>
                                      </tr>";
                                $i++;
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>No records found.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
