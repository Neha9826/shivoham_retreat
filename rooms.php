<?php
// rooms.php (USER SITE grid + availability + correct base pricing)
// include 'db.php';

// Check if a session has been started, if not, start one.
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// 📌 YOU MUST UPDATE THIS PATH WITH YOUR SUBFOLDER NAME
$basePath = ''; // For example: '/my-hotel-project/' or '/hotel/'

$check_in    = $_SESSION['check_in']    ?? '';
$check_out   = $_SESSION['check_out']   ?? '';
$no_of_rooms = $_SESSION['no_of_rooms'] ?? 1;
$guests      = $_SESSION['guests']      ?? 2;
$children    = $_SESSION['num_children'] ?? 0;

$sql = "SELECT r.*,
                (SELECT image_path FROM room_images WHERE room_id = r.id LIMIT 1) AS main_image,
                (SELECT GROUP_CONCAT(a.name, '|', a.icon_class)
                   FROM amenities a
                   JOIN room_amenities ra ON ra.amenity_id = a.id
                  WHERE ra.room_id = r.id) AS amenity_data
        FROM rooms r
        ORDER BY r.id DESC";
$roomResult = $conn->query($sql);


// ✅ Helper: build capacity text
function build_capacity_text($base, $ebCap, $maxEA, $maxEC) {
    $base = (int)$base; $ebCap = (int)$ebCap; $maxEA = (int)$maxEA; $maxEC = (int)$maxEC;
    if ($ebCap <= 0 || ($maxEA <= 0 && $maxEC <= 0)) {
        return "Base {$base}";
    }
    $bedsLabel = "extra bed" . ($ebCap > 1 ? "s" : "");
    if ($maxEA > 0 && $maxEC > 0) return "Base {$base} + up to {$maxEA} adult(s) and {$maxEC} child(ren)";
    if ($maxEA > 0) return "Base {$base} + up to {$maxEA} adult(s)";
    if ($maxEC > 0) return "Base {$base} + up to {$maxEC} child(ren)";
    return "Base {$base}";
}


// ✅ HELPER FUNCTION: get correct standard room price for a specific date
function get_standard_room_price($conn, $room_id, $date) {
    if (!$date) return null;

    // Get the full name of the day of the week, e.g., 'Wednesday'
    $dayOfWeek = date('l', strtotime($date));
    $priceColumn = strtolower($dayOfWeek) . '_standard';

    // SQL query to find the seasonal price for the specific room, date, and day.
    $sql = "SELECT {$priceColumn} FROM room_seasonal_prices
             WHERE room_id = ?
             AND ? BETWEEN start_date AND end_date
             LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $room_id, $date);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    // Return the price if found, otherwise return null
    return $row ? (float)$row[$priceColumn] : null;
}


// Process all rooms
$rooms = [];
if ($roomResult && $roomResult->num_rows > 0) {
    while ($room = $roomResult->fetch_assoc()) {
        $room_id  = (int)$room['id'];
        $total_qty = (int)$room['total_rooms'];

        // availability
        if ($check_in && $check_out) {
            $conflictSql = "
              SELECT COUNT(*) AS booked_count
              FROM booking_rooms br
              JOIN bookings b ON br.booking_id = b.id
              WHERE br.room_id = ?
                AND (b.check_in < ? AND b.check_out > ?)
            ";
            $stmt_availability = $conn->prepare($conflictSql);
            $stmt_availability->bind_param("iss", $room_id, $check_out, $check_in);
            $stmt_availability->execute();
            $conflictResult = $stmt_availability->get_result();
            $booked = $conflictResult ? (int)$conflictResult->fetch_assoc()['booked_count'] : 0;
            $available = $total_qty - $booked;
            $room['available_qty'] = max(0, $available);
        } else {
            $room['available_qty'] = null;
        }

        // main image
        $imagePath = $room['main_image'] ?? 'assets/img/default-room.jpg';
        // ✅ UPDATED PATH LOGIC: Ensure the path is correct
        if (strpos($imagePath, 'admin/') !== 0 && strpos($imagePath, 'assets/') !== 0) {
            $imagePath = 'admin/' . $imagePath;
        }
        $room['main_image'] = $basePath . $imagePath;

        // amenities
        $amenityList = [];
        if (!empty($room['amenity_data'])) {
            $pairs = explode(',', $room['amenity_data']);
            foreach ($pairs as $pair) {
                [$name, $icon] = explode('|', $pair);
                $amenityList[] = ['name' => $name, 'icon' => $icon ?: 'bi-check-circle'];
            }
        }
        $room['amenities'] = $amenityList;

        // capacity text
        $base   = (int)($room['base_adults'] ?? 0);
        $ebCap  = (int)($room['max_extra_with_bed'] ?? 0);
        $maxEA  = (int)($room['max_extra_adults'] ?? 0);
        $maxEC  = (int)($room['max_extra_children'] ?? 0);
        $room['capacity_text'] = build_capacity_text($base, $ebCap, $maxEA, $maxEC);

        // ✅ UPDATED LOGIC TO GET DYNAMIC PRICE
        // Get the current date to check for seasonal prices.
        $currentDate = date('Y-m-d');
        $price = get_standard_room_price($conn, $room_id, $currentDate);

        // If a seasonal price is not found for today, use the default from the rooms table.
        $room['price_display'] = $price ?? (float)$room['standard_price'];

        $rooms[] = $room;
    }
}

$roomDataJson = json_encode($rooms);
?>

<div class="offers_area">
    <div class="container">
        <div class="row room-card">
            <div class="col-xl-12">
                <div class="section_title text-center mb-40">
                    <span>Available Rooms</span>
                    <h3>Our Best Rooms</h3>

                    <form method="POST" action="checkAvailability.php" class="mb-5">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label>Check-in Date:</label>
                                <input type="date" name="check_in" id="check_in"
                                       value="<?= htmlspecialchars($check_in) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>Check-out Date:</label>
                                <input type="date" name="check_out" id="check_out"
                                       value="<?= htmlspecialchars($check_out) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>No. of Rooms:</label>
                                <input type="number" name="no_of_rooms" min="1"
                                       value="<?= htmlspecialchars($no_of_rooms) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>No. of Adults:</label>
                                <input type="number" name="guests" min="1"
                                       value="<?= htmlspecialchars($guests) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>No. of Children:</label>
                                <input type="number" name="num_children" min="0"
                                       value="<?= htmlspecialchars($children) ?>" class="form-control">
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary mt-2 w-100">Check Availability</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="row d-flex justify-content-center">
            <?php foreach ($rooms as $room): ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="single_offers card h-100" data-room-id="<?= $room['id'] ?>" style="cursor:pointer;">
                        
                        <img src="<?= htmlspecialchars($room['main_image']) ?>"
                             class="card-img-top" style="height:230px;object-fit:cover;" alt="Room Image">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($room['room_name']) ?></h5>

                            <strong>
                                <?php if (!is_null($room['available_qty'])): ?>
                                    <?php if ($room['available_qty'] > 0): ?>
                                        <span class="text-success"><i class="bi bi-calendar2-check"></i>
                                            <?= (int)$room['available_qty'] ?> room(s) available</span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="bi bi-calendar2-x"></i> Fully Booked</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </strong>
                            <p class="mt-2 mb-2">
                                Price: ₹<?= number_format($room['price_display'], 2) ?> / night<br>
                                Total Capacity: <?= htmlspecialchars($room['room_capacity']) ?><br>
                                <small>
                                    Adults: <?= htmlspecialchars($room['base_adults']) ?>,
                                    Extra Adult/Child with Bed: <?= htmlspecialchars($room['max_extra_with_bed']) ?>,<br>
                                    Child (age 5–12) without Bed: <?= htmlspecialchars($room['max_child_without_bed_5_12']) ?>
                                </small>
                            </p>
                            <div>
                                <?php foreach (array_slice($room['amenities'], 0, 3) as $am): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1">
                                        <i class="bi <?= htmlspecialchars($am['icon']) ?> me-1"></i>
                                        <?= htmlspecialchars($am['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($room['amenities']) > 3): ?>
                                    <span class="badge bg-secondary text-white">+<?= count($room['amenities']) - 3 ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 text-end">
                            <?php if ($room['available_qty'] > 0 || is_null($room['available_qty'])): ?>
                                <a href="room_details.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= (int)$no_of_rooms ?>&guests=<?= (int)$guests ?>&children=<?= (int)$children ?>"
                                   class="btn btn-primary">Book Now</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Not Available</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Room card click → navigate to detail page
document.querySelectorAll(".single_offers").forEach(card => {
    card.addEventListener("click", function(e) {
        // Prevent redirection if the "Book Now" button is clicked
        if (e.target.closest(".btn")) {
            return;
        }

        const roomId = this.dataset.roomId;

        // Get the current form input values for consistency
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        const rooms = document.querySelector('input[name="no_of_rooms"]').value;
        const guests = document.querySelector('input[name="guests"]').value;
        const children = document.querySelector('input[name="num_children"]').value;

        // Construct the new URL to the detailed room page
        const url = `room_details.php?room_id=${encodeURIComponent(roomId)}&check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}&no_of_rooms=${encodeURIComponent(rooms)}&guests=${encodeURIComponent(guests)}&children=${encodeURIComponent(children)}`;

        // Redirect to the new page
        window.location.href = url;
    });
});

// Helper function to handle number field input
function handleNumberInput(input) {
    input.addEventListener('input', function() {
        // Remove leading zeros
        this.value = this.value.replace(/^0+/, '');
    });
    input.addEventListener('blur', function() {
        // If the field is empty, set it to 0
        if (this.value === '' || this.value === null) {
            this.value = 0;
        }
    });
}

// Apply the new function to all number input fields
document.querySelectorAll('input[type="number"]').forEach(handleNumberInput);

// Date helpers
const checkIn  = document.getElementById('check_in');
const checkOut = document.getElementById('check_out');
const today = new Date(); const todayStr = today.toISOString().split('T')[0];
checkIn.setAttribute('min', todayStr);
checkIn.addEventListener('change', () => {
    const inDate = new Date(checkIn.value);
    if (!isNaN(inDate)) {
        inDate.setDate(inDate.getDate() + 1);
        const nextDay = inDate.toISOString().split('T')[0];
        checkOut.value = nextDay;
        checkOut.setAttribute('min', nextDay);
    }
});
if (checkIn.value) {
    const inDate = new Date(checkIn.value);
    if (!isNaN(inDate)) {
        inDate.setDate(inDate.getDate() + 1);
        const nextDay = inDate.toISOString().split('T')[0];
        checkOut.setAttribute('min', nextDay);
    }
}
</script>