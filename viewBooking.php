<?php
include 'db.php';

$booking_id = $_GET['id'] ?? ($_GET['booking_id'] ?? null);

$booking = null;
$extra_age_group_label = 'N/A';
$meal_plan_names = [];

if ($booking_id) {
    // Fetch main booking data with room name
    $stmt = $conn->prepare("SELECT b.*, r.room_name 
                            FROM bookings b
                            LEFT JOIN rooms r ON b.room_id = r.id 
                            WHERE b.id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if ($booking) {
        // Get extra bed age group label
        if (!empty($booking['extra_bed_age_group_id'])) {
            $group_id = (int)$booking['extra_bed_age_group_id'];
            $stmt = $conn->prepare("SELECT age_group FROM extra_bed_rates WHERE id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ageGroup = $result->fetch_assoc();
            if ($ageGroup) {
                $extra_age_group_label = $ageGroup['age_group'];
            }
            $stmt->close();
        }

        // Get all meal plans linked to this booking
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
                            <tr><th>Room Name</th><td><?= $booking['room_name'] ?></td></tr>
                            <tr><th>Meal Plan(s)</th>
                                <td>
                                    <?= !empty($meal_plan_names) ? implode(', ', $meal_plan_names) : 'N/A' ?>
                                </td>
                            </tr>
                            <tr><th>No. of Rooms</th><td><?= $booking['no_of_rooms'] ?></td></tr>
                            <tr><th>Adults</th><td><?= $booking['guests'] ?></td></tr>
                            <tr><th>Children</th><td><?= $booking['children'] ?></td></tr>
                            <tr><th>Extra Beds</th><td><?= $booking['extra_beds'] ?></td></tr>
                            <tr><th>Age Group (Extra Bed)</th><td><?= $extra_age_group_label ?></td></tr>
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
