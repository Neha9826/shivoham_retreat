<?php
include 'db.php';

$selected_rooms = $_POST['selected_rooms'] ?? [];
$selected_rooms = array_map('intval', $selected_rooms);

$no_of_adults = isset($_POST['guests']) ? intval($_POST['guests']) : 0;
$no_of_children = isset($_POST['children']) ? intval($_POST['children']) : 0;
$extra_beds = $_POST['extra_beds'] ?? [];
$meal_plan_ids = $_POST['meal_plan_id'] ?? [];
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';

$total = 0;
$total_capacity = 0;
$nights = 1;

if ($check_in && $check_out) {
    $checkin_date = new DateTime($check_in);
    $checkout_date = new DateTime($check_out);
    $interval = $checkin_date->diff($checkout_date);
    $nights = max(1, (int)$interval->format('%a'));
}

$room_counts = array_count_values($selected_rooms);

$total_capacity = 0;
$total = 0;

foreach ($room_counts as $room_id => $count) {
    $room_id = intval($room_id);

    $roomQuery = mysqli_query($conn, "SELECT price_per_night, room_capacity FROM rooms WHERE id = $room_id");
    if ($roomQuery && mysqli_num_rows($roomQuery) > 0) {
        $roomData = mysqli_fetch_assoc($roomQuery);
        $roomPrice = floatval($roomData['price_per_night']);
        $roomCapacity = intval($roomData['room_capacity']);

        $total += $roomPrice * $count * $nights;
        $total_capacity += $roomCapacity * $count;
    }
}
    

$total_guests = $no_of_adults + $no_of_children;
if ($total_guests > $total_capacity) {
    echo "Error: Total guests exceed capacity.";
    exit;
}

// Extra beds
foreach ($extra_beds as $age_group_id) {
    $age_group_id = intval($age_group_id);
    $bedQuery = mysqli_query($conn, "SELECT extra_price FROM extra_bed_rates WHERE id = $age_group_id");
    if ($bedData = mysqli_fetch_assoc($bedQuery)) {
        $total += floatval($bedData['extra_price']) * $nights;
    }
}

// Meal plans
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

// Optional fallback
if ($total === 0) {
    echo "Error: No valid room or pricing info found.";
    exit;
}

echo $total;
?>
