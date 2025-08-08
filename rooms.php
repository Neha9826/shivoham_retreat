<?php
include 'db.php';

$check_in     = $_SESSION['check_in'] ?? '';
$check_out    = $_SESSION['check_out'] ?? '';
$no_of_rooms  = $_SESSION['no_of_rooms'] ?? 1;
$guests       = $_SESSION['guests'] ?? 2;
$children     = $_SESSION['num_children'] ?? 0;

// Fetch all rooms with total stock and details
$sql = "SELECT r.*, 
               (SELECT image_path FROM room_images WHERE room_id = r.id LIMIT 1) AS main_image,
               (SELECT GROUP_CONCAT(a.name, '|', a.icon_class) 
                FROM amenities a 
                JOIN room_amenities ra ON ra.amenity_id = a.id 
                WHERE ra.room_id = r.id) AS amenity_data
        FROM rooms r";
$roomResult = $conn->query($sql);
$rooms = [];
if ($roomResult && $roomResult->num_rows > 0) {
    while ($room = $roomResult->fetch_assoc()) {
        $room_id = $room['id'];
        $total_qty = $room['total_rooms'];
        if ($check_in && $check_out) {
            $conflictSql = "
                SELECT COUNT(*) AS booked_count
                FROM booking_rooms br
                JOIN bookings b ON br.booking_id = b.id
                WHERE br.room_id = $room_id
                AND (
                    b.check_in < '$check_out' AND b.check_out > '$check_in'
                )
            ";
            $conflictResult = $conn->query($conflictSql);
            $booked = $conflictResult ? intval($conflictResult->fetch_assoc()['booked_count']) : 0;
            $available = $total_qty - $booked;
            $room['available_qty'] = max(0, $available);
        } else {
            $room['available_qty'] = null;
        }

        // Format image
        $imagePath = $room['main_image'] ?? 'assets/img/default-room.jpg';
        $imageSrc = str_starts_with($imagePath, 'uploads/') ? 'admin/' . $imagePath : $imagePath;
        $room['main_image'] = $imageSrc;

        // Format amenities preview
        $amenityList = [];
        if (!empty($room['amenity_data'])) {
            $pairs = explode(',', $room['amenity_data']);
            foreach ($pairs as $pair) {
                [$name, $icon] = explode('|', $pair);
                $amenityList[] = ['name' => $name, 'icon' => $icon ?: 'bi-check-circle'];
            }
        }
        $room['amenities'] = $amenityList;
        $rooms[] = $room;
    }
}

// Convert to JS-readable format
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
                <input type="number" name="no_of_rooms" min="1" value="<?= htmlspecialchars($no_of_rooms) ?>" class="form-control" required>
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

    <div class="row">
      <?php foreach ($rooms as $room): ?>
        <div class="col-xl-4 col-md-6 mb-4">
          <div class="single_offers card h-100" data-room-id="<?= $room['id'] ?>" style="cursor:pointer;">
            <img src="<?= $room['main_image'] ?>" class="card-img-top" style="height: 230px; object-fit:cover;" alt="Room Image">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($room['room_name']) ?></h5>
              <strong>
                <?php if (!is_null($room['available_qty'])): ?>
                  <?php if ($room['available_qty'] > 0): ?>
                    <span class="text-success"><i class="bi bi-calendar2-check"></i> <?= $room['available_qty'] ?> room(s) available</span>
                  <?php else: ?>
                    <span class="text-danger"><i class="bi bi-calendar2-x"></i> Fully Booked</span>
                  <?php endif; ?>
                <?php endif; ?>
              </strong>
              <p>Price: ₹<?= htmlspecialchars($room['price_per_night']) ?> / night <br>
              Capacity: <?= htmlspecialchars($room['room_capacity']) ?> guests</p>
              <div>
                <?php
                  $shownAmenities = array_slice($room['amenities'], 0, 3);
                  foreach ($shownAmenities as $am):
                ?>
                  <span class="badge bg-light text-dark border me-1 mb-1">
                    <i class="bi <?= $am['icon'] ?> me-1"></i> <?= $am['name'] ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($room['amenities']) > 3): ?>
                  <span class="badge bg-secondary text-white">+<?= count($room['amenities']) - 3 ?> more</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 text-end">
              <?php if ($room['available_qty'] > 0 || is_null($room['available_qty'])): ?>
                <a href="booking.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= $no_of_rooms ?>&guests=<?= urlencode($guests) ?>&children=<?= urlencode($children) ?>" class="btn btn-primary">Book Now</a>
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
const allRooms = <?= $roomDataJson ?>;

// Room card click → open modal
document.querySelectorAll(".single_offers").forEach(card => {
  card.addEventListener("click", function (e) {
    if (e.target.closest(".btn")) return;

    const roomId = this.dataset.roomId;
    const room = allRooms.find(r => r.id == roomId);
    if (!room) return;

    document.getElementById("modalRoomName").innerText = room.room_name;
    document.getElementById("modalRoomPrice").innerText = room.price_per_night;
    document.getElementById("modalRoomCapacity").innerText = room.room_capacity;
    document.getElementById("modalRoomAvailable").innerText = room.available_qty ?? "N/A";
    document.getElementById("modalRoomDesc").innerText = room.description;

    const amContainer = document.getElementById("modalAmenities");
    amContainer.innerHTML = room.amenities.map(a => `
      <span class="badge bg-light text-dark border me-1 mb-1">
        <i class="bi ${a.icon} me-1"></i>${a.name}
      </span>
    `).join('');

    const carouselInner = document.getElementById("carouselInner");
    carouselInner.innerHTML = `<div class="text-muted p-3">Loading images...</div>`;

    fetch("getRoomPhotos.php?room_id=" + roomId)
      .then(r => r.json())
      .then(photos => {
        if (!photos.length) {
          carouselInner.innerHTML = `<div class="text-muted p-3">No images available</div>`;
          return;
        }
        carouselInner.innerHTML = photos.map((p, i) => `
          <div class="carousel-item ${i === 0 ? 'active' : ''}">
            <img src="${p}" class="d-block w-100" style="height:300px; object-fit:cover;">
          </div>
        `).join('');
      })
      .catch(() => {
        carouselInner.innerHTML = `<div class="text-danger p-3">Error loading images</div>`;
      });

    // Remove stray modal backdrop
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

    new bootstrap.Modal(document.getElementById("roomDetailModal")).show();
  });
});

// Date handling
const checkIn = document.getElementById('check_in');
const checkOut = document.getElementById('check_out');

// Disable past dates for check-in
const today = new Date();
const todayStr = today.toISOString().split('T')[0];
checkIn.setAttribute('min', todayStr);

// Auto update checkout date and min date
checkIn.addEventListener('change', () => {
  const inDate = new Date(checkIn.value);
  if (inDate.toString() !== 'Invalid Date') {
    inDate.setDate(inDate.getDate() + 1);
    const nextDay = inDate.toISOString().split('T')[0];
    checkOut.value = nextDay;
    checkOut.setAttribute('min', nextDay);
  }
});

// On page load, also ensure checkout min matches check-in
if (checkIn.value) {
  const inDate = new Date(checkIn.value);
  inDate.setDate(inDate.getDate() + 1);
  const nextDay = inDate.toISOString().split('T')[0];
  checkOut.setAttribute('min', nextDay);
}
</script>

