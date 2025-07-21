<?php include 'session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
    <?php include 'includes/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Create Meal</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Create Meal</li>
                    </ol>

                    <?php
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $name = $_POST['meal_name'] ?? '';
                        $description = $_POST['description'];
                        $price = $_POST['price'];

                        $sql = "INSERT INTO meal_plan (name, description, price) VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssd", $name, $description, $price);

                        if (mysqli_stmt_execute($stmt)) {
                            echo '<div class="alert alert-success">Meal added successfully!</div>';
                        } else {
                            echo '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
                        }
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="meal_name" class="form-label">Meal Name</label>
                                    <input type="text" name="meal_name" class="form-control" id="meal_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" class="form-control" id="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₹)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" id="price" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Meal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
