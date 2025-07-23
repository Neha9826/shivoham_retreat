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
                    <h2>All Rooms</h2>
                    <a href="addRoom.php" class="btn btn-success">+ Add New Room</a>
                </div>
                <div class="mb-3 d-flex justify-content-end">
                    <input type="text" id="roomSearch" class="form-control w-25 shadow-sm" placeholder="Search rooms...">
                </div>
                <div class="table-responsive shadow-sm rounded bg-white p-3">
                    <table id="roomTable" class="table table-bordered table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr style="font-size: 15px; font-weight: 600; letter-spacing: 0.3px;">
                                <th class="py-3">#</th>
                                <th class="py-3">Image</th>
                                <th class="py-3">Room Name</th>
                                <th class="py-3">Capacity</th>
                                <th class="py-3">Price (₹)</th>
                                <th class="py-3">Description</th>
                                <th class="py-3">Created By</th>
                                <th class="py-3">Created At</th>
                                <th class="py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT r.*, e.name AS emp_name 
                                      FROM rooms r 
                                      LEFT JOIN emp e ON r.created_by = e.id 
                                      ORDER BY r.id DESC";
                            $result = mysqli_query($conn, $query);
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $room_id = $row['id'];
                                $image_query = "SELECT image_path FROM room_images WHERE room_id = $room_id LIMIT 1";
                                $image_result = mysqli_query($conn, $image_query);
                                $image = mysqli_fetch_assoc($image_result);
                                $imgSrc = isset($image['image_path']) ? $image['image_path'] : 'assets/img/no-image.png';
                                ?>
                                <tr>
                                    <td class="text-center"><?= $i++ ?></td>
                                    <td class="text-center">
                                        <img src="<?= $imgSrc ?>" alt="Room Image" width="70" height="60" class="rounded shadow-sm" style="object-fit:cover;">
                                    </td>
                                    <td><?= htmlspecialchars($row['room_name']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['room_capacity']) ?></td>
                                    <td class="text-end"><?= number_format($row['price_per_night'], 2) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 80, "...")) ?></td>
                                    <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                    <td class="text-nowrap"><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                                    <td class="text-center">
                                        <a href="roomDetails.php?id=<?= $room_id ?>" class="btn btn-sm btn-info text-white">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                    <script>
                        document.getElementById("roomSearch").addEventListener("keyup", function () {
                            const query = this.value.toLowerCase();
                            const rows = document.querySelectorAll("#roomTable tbody tr");

                            rows.forEach(function (row) {
                                const rowText = row.innerText.toLowerCase();
                                row.style.display = rowText.includes(query) ? "" : "none";
                            });
                        });
                    </script>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
