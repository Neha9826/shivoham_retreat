<?php
// ajaxCheckAvailability.php
header('Content-Type: application/json');
require_once 'db.php';

// ----------------- Helper Functions -----------------
function get_dates_range($start, $end) {
    $dates = [];
    $current = new DateTime($start);
    $endDt = new DateTime($end);
    while ($current < $endDt) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }
    return $dates;
}

// Calculate rooms needed (children below 5 not counted)
function rooms_needed_for_guests($room, $adults, $children_above5, $children_below5) {
    $base_adults        = (int)$room['base_adults'];
    $max_extra_with_bed = (int)$room['max_extra_with_bed'];
    $max_child_5_12     = (int)$room['max_child_without_bed_5_12'];

    $adult_cap = $base_adults + $max_extra_with_bed;
    $child_cap = $max_child_5_12;
    $total_cap = $adult_cap + $child_cap;

    if ($total_cap <= 0) return PHP_INT_MAX;

    // Children below 5 are ignored for capacity
    $total_people = $adults + $children_above5;

    $rooms_by_total    = (int)ceil($total_people / $total_cap);
    $rooms_for_adults  = (int)ceil($adults / max(1, $adult_cap));
    $rooms_for_children = $child_cap > 0 ? (int)ceil($children_above5 / $child_cap) : 0;

    return max($rooms_by_total, $rooms_for_adults, $rooms_for_children);
}

// Calculate minimum available units for a date range
function get_min_available_units($conn, $room_id, $check_in, $check_out, $total_rooms) {
    $sql = "SELECT date, COUNT(*) AS booked
              FROM room_availability
             WHERE room_id = ? AND date >= ? AND date < ?
          GROUP BY date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $room_id, $check_in, $check_out);
    $stmt->execute();
    $res = $stmt->get_result();

    $booked_by_date = [];
    while ($r = $res->fetch_assoc()) {
        $booked_by_date[$r['date']] = (int)$r['booked'];
    }

    $dates         = get_dates_range($check_in, $check_out);
    $min_available = $total_rooms;

    foreach ($dates as $d) {
        $booked    = $booked_by_date[$d] ?? 0;
        $available = $total_rooms - $booked;
        if ($available < $min_available) {
            $min_available = $available;
        }
    }
    return max(0, $min_available);
}
// ----------------- End Helper Functions -----------------

// ---- Receive and validate POST data ----
$check_in        = $_POST['check_in']  ?? null;
$check_out       = $_POST['check_out'] ?? null;
$adults          = intval($_POST['guests'] ?? ($_POST['adults'] ?? 1));
$children        = intval($_POST['num_children'] ?? ($_POST['children'] ?? 0));
$rooms_requested  = intval($_POST['no_of_rooms'] ?? ($_POST['rooms'] ?? 0));

if (!$check_in || !$check_out) {
    echo json_encode(['status' => 'error', 'message' => 'Missing check-in or check-out dates.']);
    exit;
}

// For now, treat all children as above 5
$children_above5 = $children;
$children_below5 = 0;

// ---- Fetch all rooms ----
$q       = "SELECT * FROM rooms ORDER BY id";
$result  = $conn->query($q);
$rooms   = [];

while ($room = $result->fetch_assoc()) {
    $room_id     = (int)$room['id'];
    $total_rooms = (int)$room['total_rooms'];

    // Calculate rooms needed for this room type
    $rooms_needed = rooms_needed_for_guests($room, $adults, $children_above5, $children_below5);
    if ($rooms_requested > 0) {
        $rooms_needed = max($rooms_needed, $rooms_requested);
    }

    // Calculate min available units in the range
    $min_available = get_min_available_units($conn, $room_id, $check_in, $check_out, $total_rooms);

    // Add to results (even if fully booked)
    $rooms[] = [
        'id'             => $room_id,
        'room_name'      => $room['room_name'],
        'available_qty'  => $min_available,
        'rooms_needed'   => $rooms_needed,
        'total_rooms'    => $total_rooms,
        'adult_cap'      => $room['base_adults'] + $room['max_extra_with_bed'],
        'child_cap'      => $room['max_child_without_bed_5_12']
    ];
}

// ---- Return JSON ----
echo json_encode([
    'status' => 'success',
    'rooms'  => $rooms
]);
?>
