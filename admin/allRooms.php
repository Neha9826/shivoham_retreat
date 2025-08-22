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
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Room Name</th>
                                <th>Capacity</th>
                                <th>Price (₹)</th>
                                <th>Total Rooms</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Action</th>
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

                                // Fetch main image
                                $image_query = "SELECT image_path FROM room_images WHERE room_id = $room_id LIMIT 1";
                                $image_result = mysqli_query($conn, $image_query);
                                $image = mysqli_fetch_assoc($image_result);
                                
                                // ✅ UPDATED PATH LOGIC
                                $imgSrc = isset($image['image_path']) ? htmlspecialchars(str_replace('admin/', '', $image['image_path'])) : 'assets/img/no-image.png';
                            ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" width="70" height="60" style="object-fit: cover;" class="rounded shadow-sm">
                                    </td>
                                    <td><?= htmlspecialchars($row['room_name']) ?></td>
                                    <td>
                                        Total: <?= htmlspecialchars($row['room_capacity']) ?><br>
                                        <small>
                                            Base Adults: <?= htmlspecialchars($row['base_adults']) ?>,
                                            Extra Adult/Child with Beds: <?= htmlspecialchars($row['max_extra_with_bed']) ?>,
                                            Child (5–12) without Bed: <?= htmlspecialchars($row['max_child_without_bed_5_12']) ?><br>
                                            
                                        </small>
                                    </td>
                                    <td class="text-end">₹<?= number_format($row['standard_price'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['total_rooms']) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 80, "...")) ?></td>
                                    <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                    <td><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <a href="roomDetails.php?id=<?= $room_id ?>" class="btn btn-sm btn-info text-white">View</a>
                                        <a href="editRoom.php?id=<?= $room_id ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <script>
                        document.getElementById("roomSearch").addEventListener("keyup", function () {
                            const query = this.value.toLowerCase();
                            document.querySelectorAll("#roomTable tbody tr").forEach(row => {
                                row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
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