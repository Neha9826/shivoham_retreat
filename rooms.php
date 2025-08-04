<?php
include 'db.php';
// Fetch session dates if available
$check_in = $_SESSION['check_in'] ?? '';
$check_out = $_SESSION['check_out'] ?? '';
$no_of_rooms = $_SESSION['no_of_rooms'] ?? 1;
$guests = $_SESSION['guests'] ?? 2;
$children = $_SESSION['children'] ?? 0;
// Fetch all rooms with total stock
$sql = "SELECT * FROM rooms";
$roomResult = $conn->query($sql);
$rooms = [];
if ($roomResult && $roomResult->num_rows > 0) {
    while ($room = $roomResult->fetch_assoc()) {
        $room_id = $room['id'];
        $total_qty = $room['total_rooms'];
        if ($check_in && $check_out) {
        // Count how many rooms are already booked in the selected date range
        $conflictSql = "SELECT COUNT(*) AS booked_count FROM bookings 
                        WHERE room_id = $room_id 
                        AND (check_in < '$check_out' AND check_out > '$check_in')";
            $conflictResult = $conn->query($conflictSql);
            $booked = $conflictResult->fetch_assoc()['booked_count'] ?? 0;
            $available = $total_qty - $booked;
            $room['available_qty'] = max(0, $available);
        } else {
            $room['available_qty'] = null; // unknown
        }
        $rooms[] = $room;
    }
}
// Wrap into fake result object
class ResultSet {
    private $data;
    function __construct($data) { $this->data = $data; }
    function fetch_assoc() { return array_shift($this->data); }
    function __get($name) {
        if ($name == 'num_rows') return count($this->data);
        return null;
    }
}
$result = new ResultSet($rooms);
?>
<div class="offers_area">
    <div class="container">
        <div class="row room-card">
            <div class="col-xl-12">
                <div class="section_title text-center mb-40">
                    <span>Available Rooms</span>
                    <h3>Our Best Rooms</h3>
                    <form method="POST" action="checkAvailability.php" class="mb-5">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Check-in Date:</label>
                                <input type="date" name="check_in" id="check_in" value="<?= htmlspecialchars($check_in) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>Check-out Date:</label>
                                 <input type="date" name="check_out" id="check_out" value="<?= htmlspecialchars($check_out) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>No. of Rooms:</label>
                                 <input type="number" name="num_rooms" min="1" value="<?= htmlspecialchars($no_of_rooms) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>No. of Adults:</label>
                                <input type="number" name="guests" min="1" value="<?= htmlspecialchars($guests) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label>No. of Children:</label>
                                <input type="number" name="num_children" min="0" value="<?= htmlspecialchars($children) ?>" class="form-control">
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary mt-2">Check Availability</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="row room-cards">
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="col-xl-4 col-md-4">
                    <div class="single_offers">
                        <div class="about_thumb  flex-fill">
                            <?php
                                $roomId = $room['id'];
                                $imageSql = "SELECT image_path FROM room_images WHERE room_id = $roomId LIMIT 1";
                                $imageResult = $conn->query($imageSql);
                                $imageRow = ($imageResult && $imageResult->num_rows > 0) ? $imageResult->fetch_assoc() : null;
                                $imagePath = $imageRow['image_path'] ?? 'assets/img/default-room.jpg';

                                $imageSrc = str_starts_with($imagePath, 'uploads/') ? 'admin/' . $imagePath : $imagePath;
                            ?>
                            <img src="<?= htmlspecialchars($imageSrc); ?>" alt="Room Image" style="width:100%; height:250px; object-fit:cover;">
                        </div>
                        <h3><?= htmlspecialchars($room['room_name']); ?></h3>
                        <ul>
                            <li>Capacity: <?= htmlspecialchars($room['room_capacity']); ?> persons</li>
                            <li>Price: ₹<?= htmlspecialchars($room['price_per_night']); ?> / night</li>
                            <?php if (!is_null($room['available_qty'])): ?>
                                <li><strong><?= $room['available_qty'] > 0 ? $room['available_qty'] . ' room(s) available' : 'Fully Booked'; ?></strong></li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($room['available_qty'] > 0 || is_null($room['available_qty'])): ?>
                            <!-- <a href="booking.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= $no_of_rooms ?>&guests=<?= $guests ?>&children=<?= $children ?>"
                                class="book_now popup-with-form">
                                Book Now
                            </a> -->
                            <a href="booking.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= urlencode($no_of_rooms) ?>&guests=<?= urlencode($guests) ?>&children=<?= urlencode($children) ?>" class="btn btn-primary">
                                Book Now
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Not Available</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<script>
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    checkIn.addEventListener('change', () => {
        const inDate = new Date(checkIn.value);
        if (inDate.toString() !== 'Invalid Date') {
            inDate.setDate(inDate.getDate() + 1);
            const nextDay = inDate.toISOString().split('T')[0];
            checkOut.value = nextDay;
        }
    });
</script>