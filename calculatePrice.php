<?php
include 'db.php';

$room_id = intval($_POST['room_id'] ?? 0);
$no_of_rooms = intval($_POST['no_of_rooms'] ?? 1);
$extra_beds = $_POST['extra_beds'] ?? []; // array of age group IDs
$meal_plan_ids = $_POST['meal_plan_id'] ?? []; // array of meal plan IDs
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

$total = 0;

// Calculate number of nights
$nights = 1;
if ($check_in && $check_out) {
    $checkin_date = new DateTime($check_in);
    $checkout_date = new DateTime($check_out);
    $interval = $checkin_date->diff($checkout_date);
    $nights = max(1, (int)$interval->format('%a'));
}

// Get room price
$roomQuery = mysqli_query($conn, "SELECT price_per_night FROM rooms WHERE id = $room_id");
if ($roomData = mysqli_fetch_assoc($roomQuery)) {
    $roomPrice = floatval($roomData['price_per_night']);
    $total += $roomPrice * $nights * $no_of_rooms;
}

// Add extra bed charges
foreach ($extra_beds as $age_group_id) {
    $age_group_id = intval($age_group_id);
    $bedQuery = mysqli_query($conn, "SELECT extra_price FROM extra_bed_rates WHERE id = $age_group_id");
    if ($bedData = mysqli_fetch_assoc($bedQuery)) {
        $total += floatval($bedData['extra_price']) * $nights;
    }
}

// Add meal plan prices (for all selected plans)
if (!empty($meal_plan_ids)) {
    foreach ($meal_plan_ids as $plan_id) {
        $plan_id = intval($plan_id);
        $mealQuery = mysqli_query($conn, "SELECT price FROM meal_plan WHERE id = $plan_id");
        if ($mealData = mysqli_fetch_assoc($mealQuery)) {
            $total += floatval($mealData['price']) * $nights * $no_of_rooms;
        }
    }
}

echo $total;
?>
