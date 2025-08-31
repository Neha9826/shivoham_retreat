<?php
// calculateBookingPrice.php
include 'db.php';

header('Content-Type: text/html');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<p class="text-danger">Invalid request method.</p>';
    exit;
}

$roomId         = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$checkIn        = $_POST['check_in'] ?? '';
$checkOut       = $_POST['check_out'] ?? '';
$noOfRooms      = isset($_POST['no_of_rooms']) ? intval($_POST['no_of_rooms']) : 1;
$guests         = isset($_POST['guests']) ? intval($_POST['guests']) : 2;
$children       = isset($_POST['children']) ? intval($_POST['children']) : 0;
$mealPlanKey    = $_POST['meal_plan'] ?? 'standard';
$childAges      = $_POST['child_ages'] ?? [];

// ✅ Prices sent from booking.php (meal-plan specific)
$roomPrice       = (float)($_POST['room_price'] ?? 0);
$extraBedPrice   = (float)($_POST['extra_bed_price'] ?? 0);
$child5_12Price  = (float)($_POST['child_5_12_price'] ?? 0);
$childBelow5Price= (float)($_POST['child_below_5_price'] ?? 0);

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

// Fetch room details for capacity checks
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
$totalBaseAdultsCapacity = $roomDetails['base_adults'] * $noOfRooms;
$extraAdultsNeedingBeds = max(0, $guests - $totalBaseAdultsCapacity);
$totalAllowedExtraBeds = $roomDetails['max_extra_with_bed'] * $noOfRooms;
$totalExtraBedsNeeded = min($extraAdultsNeedingBeds, $totalAllowedExtraBeds);

// Capacity checks for children
$totalChildrenBelow5Capacity = $roomDetails['max_child_without_bed_below_5'] * $noOfRooms;
$totalChildren5_12Capacity   = $roomDetails['max_child_without_bed_5_12'] * $noOfRooms;

if ($children_below_5_count > $totalChildrenBelow5Capacity) {
    echo '<p class="text-danger fw-bold">Number of children below 5 exceeds room capacity. Please adjust.</p>';
    exit;
}
if ($children_5_12_count > $totalChildren5_12Capacity) {
    echo '<p class="text-danger fw-bold">Number of children 5-12 exceeds room capacity. Please adjust.</p>';
    exit;
}

$maxTotalGuestsAllowed = ($roomDetails['base_adults'] * $noOfRooms) +
                         ($roomDetails['max_child_without_bed_below_5'] * $noOfRooms) +
                         ($roomDetails['max_child_without_bed_5_12'] * $noOfRooms) +
                         ($roomDetails['max_extra_with_bed'] * $noOfRooms);

if (($guests + $children) > $maxTotalGuestsAllowed) {
    echo '<p class="text-danger fw-bold">Your total guest count (Adults + Children) exceeds the maximum capacity for the number of rooms selected. Please reduce the number of guests or increase rooms.</p>';
    exit;
}

// --- PRICE CALCULATIONS ---

// 1. Base room cost with seasonal override
$dayOfWeek = date('l', strtotime($checkIn));
$seasonalPriceColumn = strtolower($dayOfWeek) . '_' . str_replace('-', '_', $mealPlanKey);

$sql_prices = "SELECT `{$seasonalPriceColumn}` FROM room_seasonal_prices WHERE room_id = ? AND ? BETWEEN start_date AND end_date LIMIT 1";
$stmt_prices = $conn->prepare($sql_prices);
$stmt_prices->bind_param("is", $roomId, $checkIn);
$stmt_prices->execute();
$seasonal_prices = $stmt_prices->get_result()->fetch_assoc();

// ✅ If seasonal price exists, override the passed roomPrice
$basePricePerNight = $seasonal_prices[$seasonalPriceColumn] ?? $roomPrice;
$roomCost = $basePricePerNight * $noOfRooms * $numNights;

// 2. Extra bed cost
$extraBedCost = $totalExtraBedsNeeded * $extraBedPrice * $numNights;

// 3. Child cost
$totalChildCost  = $children_5_12_count * $child5_12Price * $numNights;
$totalChildCost += $children_below_5_count * $childBelow5Price * $numNights;

// 4. Total before tax
$totalPreTax = $roomCost + $extraBedCost + $totalChildCost;

// 5. Taxes (currently 0)
$taxesAndFees = 0;

// 6. Final total
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
    <span class="fw-bold">₹<?= number_format($child5_12Price * $children_5_12_count * $numNights, 2) ?></span>
</div>
<?php endif; ?>

<?php if ($children_below_5_count > 0): ?>
<div class="d-flex justify-content-between mb-2">
    <span>Child (&lt;5) Charges (<?= $children_below_5_count ?>)</span>
    <span class="fw-bold">₹<?= number_format($childBelow5Price * $children_below_5_count * $numNights, 2) ?></span>
</div>
<?php endif; ?>

<hr class="my-2">
<div class="d-flex justify-content-between align-items-center">
    <span class="h5 mb-0">Total Amount</span>
    <span class="h5 fw-bold mb-0">₹<?= number_format($totalAmount, 2) ?></span>
</div>
