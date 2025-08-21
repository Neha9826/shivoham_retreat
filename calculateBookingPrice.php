<?php
// calculateBookingPrice.php
include 'db.php';

header('Content-Type: text/html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<p class="text-danger">Invalid request method.</p>';
    exit;
}

$roomId      = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$checkIn     = $_POST['check_in'] ?? '';
$checkOut    = $_POST['check_out'] ?? '';
$noOfRooms   = isset($_POST['no_of_rooms']) ? intval($_POST['no_of_rooms']) : 1;
$guests      = isset($_POST['guests']) ? intval($_POST['guests']) : 2;
$children    = isset($_POST['children']) ? intval($_POST['children']) : 0;
$mealPlanKey = $_POST['meal_plan'] ?? 'standard';
$childAges   = $_POST['child_ages'] ?? [];

function get_num_nights($checkIn, $checkOut) {
    if (!$checkIn || !$checkOut) return 0;
    $date1 = new DateTime($checkIn);
    $date2 = new DateTime($checkOut);
    $diff = $date1->diff($date2);
    return max(1, $diff->days);
}

$numNights = get_num_nights($checkIn, $checkOut);

if ($roomId <= 0 || $numNights === 0) {
    echo '<p class="text-danger">Invalid booking details.</p>';
    exit;
}

// Fetch room details
$roomSql = "SELECT * FROM rooms WHERE id = ?";
$stmt = $conn->prepare($roomSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$roomDetails = $stmt->get_result()->fetch_assoc();

if (!$roomDetails) {
    echo '<p class="text-danger">Room not found.</p>';
    exit;
}

// --- CORE CAPACITY AND PRICE LOGIC ---

$totalExtraBedsNeeded = 0;
$children_5_12_count = 0;
$children_below_5_count = 0;

foreach ($childAges as $age) {
    if ($age == 1) { // Age 5-12
        $children_5_12_count++;
    } else { // Age below 5
        $children_below_5_count++;
    }
}

// Per-room guest allocation and extra bed calculation
// Calculate total base adults capacity for all rooms
$totalBaseAdultsCapacity = $roomDetails['base_adults'] * $noOfRooms;
// Calculate extra adults needing beds
$extraAdultsNeedingBeds = max(0, $guests - $totalBaseAdultsCapacity);

// Ensure extra adults needing beds does not exceed total max_extra_with_bed for all rooms
$totalAllowedExtraBeds = $roomDetails['max_extra_with_bed'] * $noOfRooms;
$totalExtraBedsNeeded = min($extraAdultsNeedingBeds, $totalAllowedExtraBeds);

// Capacity check for children without beds (they don't consume extra beds directly, but room capacity)
// This part is for strict validation if children without beds exceed their *specific* limits
$totalChildrenBelow5Capacity = $roomDetails['max_child_without_bed_below_5'] * $noOfRooms;
$totalChildren5_12Capacity = $roomDetails['max_child_without_bed_5_12'] * $noOfRooms;

if ($children_below_5_count > $totalChildrenBelow5Capacity) {
    echo '<p class="text-danger fw-bold">Number of children below 5 exceeds room capacity. Please adjust.</p>';
    exit;
}
if ($children_5_12_count > $totalChildren5_12Capacity) {
    echo '<p class="text-danger fw-bold">Number of children 5-12 exceeds room capacity. Please adjust.</p>';
    exit;
}


// Check if total guests exceed total capacity (base adults + all children + all extra beds)
$maxTotalGuestsAllowed = ($roomDetails['base_adults'] * $noOfRooms) + 
                         ($roomDetails['max_child_without_bed_below_5'] * $noOfRooms) + 
                         ($roomDetails['max_child_without_bed_5_12'] * $noOfRooms) + 
                         ($roomDetails['max_extra_with_bed'] * $noOfRooms); // Total extra bed slots
                         
// This is a more comprehensive check for overall occupancy
if (($guests + $children) > $maxTotalGuestsAllowed) {
    echo '<p class="text-danger fw-bold">Your total guest count (Adults + Children) exceeds the maximum capacity for the number of rooms selected. Please reduce the number of guests or increase rooms.</p>';
    exit;
}


// 1. Calculate base room price
$dayOfWeek = date('l', strtotime($checkIn));
$priceColumn = strtolower($dayOfWeek) . '_' . $mealPlanKey;
$sql_prices = "SELECT `{$priceColumn}` FROM room_seasonal_prices WHERE room_id = ? AND ? BETWEEN start_date AND end_date LIMIT 1";
$stmt_prices = $conn->prepare($sql_prices);
$stmt_prices->bind_param("is", $roomId, $checkIn);
$stmt_prices->execute();
$seasonal_prices = $stmt_prices->get_result()->fetch_assoc();
$fallbackPriceColumn = ($mealPlanKey === 'standard') ? 'standard_price' : 'price_with_' . $mealPlanKey;
$basePricePerNight = $seasonal_prices[$priceColumn] ?? $roomDetails[$fallbackPriceColumn];

$roomCost = $basePricePerNight * $noOfRooms * $numNights;

// 2. Calculate final extra bed cost
$extraBedCost = $totalExtraBedsNeeded * $roomDetails['price_with_extra_bed'] * $numNights;

// 3. Calculate child cost based on ages
$totalChildCost = 0;
$totalChildCost += $children_5_12_count * $roomDetails['price_child_5_12'];
$totalChildCost += $children_below_5_count * $roomDetails['price_child_below_5'];
$totalChildCost *= $numNights;

// 4. Calculate Taxes & Fees (this section is now commented out)
/*
$taxesAndFeesRate = 0.18; 
$totalPreTax = $roomCost + $extraBedCost + $totalChildCost;
$taxesAndFees = $totalPreTax * $taxesAndFeesRate;
*/

$totalPreTax = $roomCost + $extraBedCost + $totalChildCost;
$taxesAndFees = 0; // Set to 0 since it's commented out

// 5. Calculate total amount
$totalAmount = $totalPreTax + $taxesAndFees;

?>

<div class="d-flex justify-content-between mb-2">
    <span>Base Price (<?= $noOfRooms ?> Room<?= $noOfRooms > 1 ? 's' : '' ?> x <?= $numNights ?> Night<?= $numNights > 1 ? 's' : '' ?>)</span>
    <span class="fw-bold">₹<?= number_format($roomCost, 2) ?></span>
</div>

<?php if ($extraBedCost > 0): ?>
<div class="d-flex justify-content-between mb-2">
    <span>Extra Bed<?= $totalExtraBedsNeeded > 1 ? 's' : '' ?> (<?= $totalExtraBedsNeeded ?>)</span>
    <span class="fw-bold">₹<?= number_format($extraBedCost, 2) ?></span>
</div>
<?php endif; ?>

<?php if ($children_5_12_count > 0): ?>
<div class="d-flex justify-content-between mb-2">
    <span>Child (5-12) Charges (<?= $children_5_12_count ?>)</span>
    <span class="fw-bold">₹<?= number_format($roomDetails['price_child_5_12'] * $children_5_12_count * $numNights, 2) ?></span>
</div>
<?php endif; ?>

<?php if ($children_below_5_count > 0): ?>
<div class="d-flex justify-content-between mb-2">
    <span>Child (&lt;5) Charges (<?= $children_below_5_count ?>)</span>
    <span class="fw-bold">₹<?= number_format($roomDetails['price_child_below_5'] * $children_below_5_count * $numNights, 2) ?></span>
</div>
<?php endif; ?>

<?php /*
<hr class="my-2">
<div class="d-flex justify-content-between mb-2">
    <span>Taxes & Service Fees</span>
    <span class="fw-bold">₹<?= number_format($taxesAndFees, 2) ?></span>
</div>
<hr class="my-2">
*/ ?>

<div class="d-flex justify-content-between align-items-center">
    <span class="h5 mb-0">Total Amount</span>
    <span class="h5 fw-bold mb-0">₹<?= number_format($totalAmount, 2) ?></span>
</div>