<?php
include 'db.php';

$booking_id = $_GET['id'] ?? ($_GET['booking_id'] ?? null);

$booking = null;
$extra_age_groups = [];
$room_names = [];
$meal_plan_names = [];

if ($booking_id) {
    // Fetch main booking data
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if ($booking) {
        // ✅ Fetch all selected rooms
        $roomStmt = $conn->prepare("
            SELECT r.room_name 
            FROM booking_rooms br 
            JOIN rooms r ON br.room_id = r.id 
            WHERE br.booking_id = ?
        ");
        $roomStmt->bind_param("i", $booking_id);
        $roomStmt->execute();
        $roomResult = $roomStmt->get_result();
        while ($row = $roomResult->fetch_assoc()) {
            $room_names[] = $row['room_name'];
        }
        $roomStmt->close();

        // ✅ Fetch all extra bed age groups
        $bedStmt = $conn->prepare("
            SELECT e.age_group 
            FROM booking_extra_beds beb 
            JOIN extra_bed_rates e ON beb.extra_bed_id = e.id 
            WHERE beb.booking_id = ?
        ");
        $bedStmt->bind_param("i", $booking_id);
        $bedStmt->execute();
        $bedResult = $bedStmt->get_result();
        while ($row = $bedResult->fetch_assoc()) {
            $extra_age_groups[] = $row['age_group'];
        }
        $bedStmt->close();

        // ✅ Meal Plans (already correct)
        $stmt = $conn->prepare("SELECT m.name 
                                FROM booking_meal_plans bmp
                                JOIN meal_plan m ON bmp.meal_plan_id = m.id
                                WHERE bmp.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $meal_plan_names[] = $row['name'];
        }
        $stmt->close();
    }
}
?>


<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/header.php'; ?>

<div class="bradcam_area breadcam_bg_1">
    <h3>Booking Details</h3>
</div>

<section class="about_area" style="padding: 50px 0 30px;">
    <div class="container">
        <?php if ($booking): ?>
            <div class="section_title text-center mb-40">
                <h3>Thank you, <?= htmlspecialchars($booking['name']) ?>!</h3>
                <p>Here are your booking details:</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr><th>Booking ID</th><td><?= $booking['id'] ?></td></tr>
                            <tr><th>Room Name</th><td><?= !empty($room_names) ? implode(', ', $room_names) : ($booking['room_name'] ?? 'N/A') ?></td>   </tr>
                            <tr><th>Meal Plan(s)</th>
                                <td>
                                    <?= !empty($meal_plan_names) ? implode(', ', $meal_plan_names) : 'N/A' ?>
                                </td>
                            </tr>
                            <tr><th>No. of Rooms</th><td><?= $booking['no_of_rooms'] ?></td></tr>
                            <tr><th>Adults</th><td><?= $booking['guests'] ?></td></tr>
                            <tr><th>Children</th><td><?= $booking['children'] ?></td></tr>
                            <tr><th>Extra Beds</th><td><?= $booking['extra_beds'] ?></td></tr>
                            <tr><th>Age Group (Extra Bed)</th><td><?= !empty($extra_age_groups) ? implode(', ', $extra_age_groups) : 'N/A' ?></td></tr>
                            <tr><th>Check-In</th><td><?= date("d M Y", strtotime($booking['check_in'])) ?></td></tr>
                            <tr><th>Check-Out</th><td><?= date("d M Y", strtotime($booking['check_out'])) ?></td></tr>
                            <tr><th>Email</th><td><?= $booking['email'] ?></td></tr>
                            <tr><th>Phone</th><td><?= $booking['phone'] ?></td></tr>
                            <tr><th>Total Price</th><td>₹<?= $booking['total_price'] ?></td></tr>
                            <tr><th>Status</th><td><?= ucfirst($booking['status']) ?></td></tr>
                        </tbody>
                    </table>
                    <div class="text-center mt-4">
                        <a href="index.php" class="boxed-btn3">Go to Home</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">
                Booking not found or invalid ID.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/form.php'; ?>
</body>
</html>
