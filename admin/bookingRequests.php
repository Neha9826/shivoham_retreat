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
                                    <th>Child Ages</th> <!-- Changed from Extra Bed Age Group -->
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

                                // Define meal plan names for display
                                $meal_plan_names = [
                                    'standard'          => 'Room Only',
                                    'breakfast'         => 'Room with Breakfast',
                                    'breakfast_lunch'   => 'Room with Breakfast & Lunch',
                                    'all_meals'         => 'All Meals'
                                ];

                                // Query to fetch booking data
                                $query = "SELECT b.*, r.room_name
                                          FROM bookings b
                                          LEFT JOIN rooms r ON b.room_id = r.id
                                          ORDER BY b.id DESC";

                                $result = mysqli_query($conn, $query);
                                if (!$result) {
                                    echo "Error fetching bookings: " . mysqli_error($conn);
                                } else {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Process child_ages_json
                                        $childAgesDisplay = '-';
                                        if (!empty($row['child_ages_json'])) {
                                            $childAges = json_decode($row['child_ages_json'], true);
                                            if (is_array($childAges)) {
                                                $displayAges = [];
                                                foreach ($childAges as $ageCode) {
                                                    if ($ageCode == '0') {
                                                        $displayAges[] = 'Below 5';
                                                    } elseif ($ageCode == '1') {
                                                        $displayAges[] = '5-12';
                                                    }
                                                }
                                                $childAgesDisplay = implode(', ', $displayAges);
                                            }
                                        }

                                        // Get meal plan name
                                        $mealPlanDisplayName = $meal_plan_names[$row['meal_plan']] ?? 'N/A';
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
                                            <td><?= $childAgesDisplay; ?></td> <!-- Display parsed child ages -->
                                            <td><?= htmlspecialchars($mealPlanDisplayName); ?></td>
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
                                <?php
                                    }
                                }
                                ?>
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