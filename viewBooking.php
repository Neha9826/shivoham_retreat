<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = $_GET['id'] ?? ($_GET['booking_id'] ?? null);
$user_email = $_SESSION['user_email'] ?? null;

$booking = null;
$extra_age_groups = [];
$room_names = [];
$meal_plan_names = [];

if ($booking_id && $user_email) {
    // Secure: only allow viewing if email matches user's email
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $booking_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if ($booking) {
        // ✅ Fetch room names (multiple)
        $stmt = $conn->prepare("
            SELECT r.room_name 
            FROM booking_rooms br 
            JOIN rooms r ON br.room_id = r.id 
            WHERE br.booking_id = ?
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $room_names[] = $row['room_name'];
        }
        $stmt->close();

        // ✅ Fetch extra bed age groups (multiple)
        $stmt = $conn->prepare("
            SELECT e.age_group 
            FROM booking_extra_beds beb 
            JOIN extra_bed_rates e ON beb.extra_bed_id = e.id 
            WHERE beb.booking_id = ?
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $extra_age_groups[] = $row['age_group'];
        }
        $stmt->close();

        // ✅ Fetch meal plans (multiple)
        $stmt = $conn->prepare("
            SELECT m.name 
            FROM booking_meal_plans bmp
            JOIN meal_plan m ON bmp.meal_plan_id = m.id
            WHERE bmp.booking_id = ?
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
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
                            <tr><th>Room Name(s)</th>
                                <td><?= !empty($room_names) ? implode(', ', $room_names) : ($booking['room_name'] ?? 'N/A') ?></td>
                            </tr>
                            <tr><th>Meal Plan(s)</th>
                                <td><?= !empty($meal_plan_names) ? implode(', ', $meal_plan_names) : 'N/A' ?></td>
                            </tr>
                            <tr><th>No. of Rooms</th><td><?= $booking['no_of_rooms'] ?></td></tr>
                            <tr><th>Adults</th><td><?= $booking['guests'] ?></td></tr>
                            <tr><th>Children</th><td><?= $booking['children'] ?></td></tr>
                            <tr><th>Extra Beds</th><td><?= $booking['extra_beds'] ?></td></tr>
                            <tr><th>Age Group (Extra Bed)</th>
                                <td><?= !empty($extra_age_groups) ? implode(', ', $extra_age_groups) : 'N/A' ?></td>
                            </tr>
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
                Booking not found or access denied.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/form.php'; ?>
</body>
</html>
