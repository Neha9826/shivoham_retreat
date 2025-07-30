<?php
include 'db.php';

$room_id = intval($_POST['room_id'] ?? 0);
$no_of_rooms = intval($_POST['no_of_rooms'] ?? 1);
$no_of_adults = isset($_POST['guests']) ? intval($_POST['guests']) : 0;
$no_of_children = isset($_POST['children']) ? intval($_POST['children']) : 0;
$extra_beds = $_POST['extra_beds'] ?? []; // array of age group ids
$meal_plan_ids = $_POST['meal_plan_id'] ?? []; // this is now an array
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

$total = 0;

// Calculate nights
$nights = 1;
if ($check_in && $check_out) {
    $checkin_date = new DateTime($check_in);
    $checkout_date = new DateTime($check_out);
    $interval = $checkin_date->diff($checkout_date);
    $nights = max(1, (int)$interval->format('%a'));
}

// Get room price
$roomQuery = mysqli_query($conn, "SELECT price_per_night, room_capacity FROM rooms WHERE id = $room_id");
if ($roomData = mysqli_fetch_assoc($roomQuery)) {
    $roomPrice = floatval($roomData['price_per_night']);
    $room_capacity = intval($roomData['room_capacity']);

    // Validate capacity
    if (($no_of_adults + $no_of_children) > ($room_capacity * $no_of_rooms)) {
        echo "Error: Total guests exceed capacity.";
        exit;
    }

    $total += $roomPrice * $no_of_rooms * $nights;
}

// Extra bed prices (based on age group ids)
foreach ($extra_beds as $age_group_id) {
    $age_group_id = intval($age_group_id);
    $bedQuery = mysqli_query($conn, "SELECT extra_price FROM extra_bed_rates WHERE id = $age_group_id");
    if ($bedData = mysqli_fetch_assoc($bedQuery)) {
        $total += floatval($bedData['extra_price']) * $nights;
    }
}

// ✅ Handle multiple meal plans
$meal_plan_ids = $_POST['meal_plan_id'] ?? [];

if (!empty($meal_plan_ids) && is_array($meal_plan_ids)) {
    foreach ($meal_plan_ids as $meal_id) {
        $meal_id = intval($meal_id);
        $mealQuery = mysqli_query($conn, "SELECT price FROM meal_plan WHERE id = $meal_id");
        if ($mealData = mysqli_fetch_assoc($mealQuery)) {
            $meal_price = floatval($mealData['price']);
            $total += $meal_price * $no_of_adults * $nights;
        }
    }
}


echo $total;
?>
