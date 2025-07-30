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
                <h1 class="mt-4">Booking Requests</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Booking Requests</li>
                </ol>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Booking Requests List
                    </div>
                    <div class="card-body">
                        <table id="datatablesSimple">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Room</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Adults</th>
                                    <th>Children</th>
                                    <th>Extra Beds</th>
                                    <th>Extra Bed Age Group</th>
                                    <th>Meal Plan</th>
                                    <th>Total Price</th>
                                    <th>Booking Date</th>
                                    <th>Status</th>
                                    <th>No. of Rooms</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include 'db.php';
                                $query = "SELECT b.*, r.room_name
          FROM bookings b
          LEFT JOIN rooms r ON b.room_id = r.id
          ORDER BY b.id DESC";

                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Get Extra Bed Age Group Names
                                    $ageGroupNames = '';
                                    if (!empty($row['extra_bed_age_group_id'])) {
                                        $ids = explode(',', $row['extra_bed_age_group_id']);
                                        $safe_ids = implode(',', array_map('intval', $ids));
                                        $age_query = "SELECT age_group FROM extra_bed_rates WHERE id IN ($safe_ids)";
                                        $age_result = mysqli_query($conn, $age_query);
                                        $names = [];
                                        while ($age = mysqli_fetch_assoc($age_result)) {
                                            $names[] = $age['age_group'];
                                        }
                                        $ageGroupNames = implode(', ', $names);
                                    }
                                ?>
                                    <tr>
                                        <td><?= $row['id']; ?></td>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= htmlspecialchars($row['phone']); ?></td>
                                        <td><?= htmlspecialchars($row['room_name']); ?></td>
                                        <td><?= $row['check_in']; ?></td>
                                        <td><?= $row['check_out']; ?></td>
                                        <td><?= $row['guests']; ?></td>
                                        <td><?= $row['children']; ?></td>
                                        <td><?= $row['extra_beds']; ?></td>
                                        <td><?= $ageGroupNames ?: '-'; ?></td>
                                        <td>
                                            <?php
                                            $meal_plan_query = "SELECT m.name FROM booking_meal_plans bmp
                                                                JOIN meal_plan m ON bmp.meal_plan_id = m.id
                                                                WHERE bmp.booking_id = " . intval($row['id']);
                                            $meal_plan_result = mysqli_query($conn, $meal_plan_query);
                                            $meal_names = [];
                                            while ($mp = mysqli_fetch_assoc($meal_plan_result)) {
                                                $meal_names[] = $mp['name'];
                                            }
                                            echo !empty($meal_names) ? htmlspecialchars(implode(', ', $meal_names)) : '-';
                                            ?>
                                        </td>
                                        <td>₹<?= number_format($row['total_price'], 2); ?></td>
                                        <td><?= $row['booking_date']; ?></td>
                                        <td><?= ucfirst($row['status']); ?></td>
                                        <td><?= $row['no_of_rooms']; ?></td>
                                        <td>
                                            <form method="POST" action="updateBookingStatus.php" style="display:flex;">
                                                <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                                                <select name="status" class="form-select form-select-sm me-2" required>
                                                    <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="booked" <?= $row['status'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                                                    <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
