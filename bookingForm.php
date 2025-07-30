<?php
include 'db.php';
$room_id = $_GET['room_id'] ?? '';
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$no_of_rooms = $_GET['no_of_rooms'] ?? 1;
$guests = $_GET['guests'] ?? 2;
$children = $_GET['children'] ?? 0;



// Fetch data
$extraBedQuery = mysqli_query($conn, "SELECT * FROM extra_bed_rates");
$mealPlanQuery = mysqli_query($conn, "SELECT * FROM meal_plan");
$roomsQuery = mysqli_query($conn, "SELECT id, room_name, price_per_night, total_rooms, room_capacity FROM rooms");

$selectedRoomId = $_GET['room_id'] ?? '';
$room_id = $selectedRoomId;
$total_rooms = 10;
$room_capacity = 0;

if ($room_id) {
    $stmt = $conn->prepare("SELECT total_rooms, room_capacity FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $total_rooms = intval($row['total_rooms']);
        $room_capacity = intval($row['room_capacity']);
    }
    $stmt->close();
}
?>
<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm border-0">
        <div class="card-header text-center bg-light">
          <h4 class="mb-0">Book Your Stay at Shivoham Retreat</h4>
        </div>
        <div class="card-body booking-section">
          <form id="bookingForm" method="POST" action="submitBooking.php">
            <!-- room selector -->
            <div class="form-group">
              <label>Select Room:</label>
              <select id="room_id_display" class="form-control" disabled>
                <?php while ($r = mysqli_fetch_assoc($roomsQuery)): ?>
                  <option value="<?= $r['id'] ?>" <?= ($r['id'] == $room_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['room_name']) ?> – ₹<?= $r['price_per_night'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <select name="room_id" id="room_id" class="form-control d-none">
                <?php mysqli_data_seek($roomsQuery, 0);?>
                <?php while ($r = mysqli_fetch_assoc($roomsQuery)): ?>
                  <option value="<?= $r['id'] ?>" <?= ($r['id'] == $room_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['room_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <!-- other fields -->
            <div class="form-row">
              <div class="form-group">
                <label>Number of Rooms:</label>
                <select name="no_of_rooms" id="no_of_rooms" class="form-control">
  <?php for ($i = 1; $i <= $total_rooms; $i++): ?>
    <option value="<?= $i ?>" <?= ($i == $no_of_rooms) ? 'selected' : '' ?>><?= $i ?></option>
  <?php endfor; ?>
</select>
                <!-- Room availability message -->
<p id="roomAvailabilityMsg" style="font-weight:bold; color:#d9534f;"></p>

              </div>
              <div class="form-group col-md-4">
                <label>Check-in:</label>
                <!-- <input type="date" name="check_in" id="check_in" required class="form-control" required> -->
                <input type="date" name="check_in" id="check_in" class="form-control" value="<?= htmlspecialchars($check_in) ?>" required>
              </div>
              <div class="form-group col-md-4">
                <label>Check-out:</label>
                <!-- <input type="date" name="check_out" id="check_out" required class="form-control" required> -->
                <input type="date" name="check_out" id="check_out" class="form-control" value="<?= htmlspecialchars($check_out) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>No. of Adults:</label>
                <select name="guests" id="guests" class="form-control" required>
  <?php for ($i = 1; $i <= 10; $i++): ?>
    <option value="<?= $i ?>" <?= ($i == $guests) ? 'selected' : '' ?>><?= $i ?></option>
  <?php endfor; ?>
</select>
              </div>
              <div class="form-group col-md-6">
                <label>No. of Children:</label>
                <select name="children" id="children" class="form-control" required>
  <?php for ($i = 0; $i <= 5; $i++): ?>
    <option value="<?= $i ?>" <?= ($i == $children) ? 'selected' : '' ?>><?= $i ?></option>
  <?php endfor; ?>
</select>
                <p id="capacityMessage" style="font-weight:bold; color:green;"></p>
              </div>
            </div>

            <div class="form-group">
              <label>Extra Bed Age Group:</label>
              <div id="extraBedContainer"></div>
            </div>

            <div class="form-group">
              <label>Meal Plan:</label>
              <?php mysqli_data_seek($mealPlanQuery, 0);?>
              <?php while ($mp = mysqli_fetch_assoc($mealPlanQuery)): ?>
                <div class="form-check">
                  <input class="form-check-input meal-plan" type="checkbox" name="meal_plan_id[]" value="<?= $mp['id'] ?>" data-price="<?= $mp['price'] ?>" id="meal<?= $mp['id'] ?>">
                  <label class="form-check-label" for="meal<?= $mp['id'] ?>">
                    <?= htmlspecialchars($mp['name']) ?> (₹<?= $mp['price'] ?>)
                  </label>
                </div>
              <?php endwhile; ?>
            </div>

            <hr>
            <div class="form-group"><label>Your Name:</label><input type="text" name="name" required class="form-control" required></div>
            <div class="form-row">
              <div class="form-group col-md-6"><label>Email:</label><input type="email" name="email" required class="form-control" required></div>
              <div class="form-group col-md-6"><label>Phone:</label><input type="tel" name="phone" required class="form-control" required></div>
            </div>

            <div class="form-group"><label>Total Price (₹):</label><input type="text" name="total_price" id="total_price" class="form-control" readonly></div>

            <div id="availabilityMessage"></div>
            <button type="submit" id="submitBooking" class="btn btn-primary" disabled>Submit Booking</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
mysqli_data_seek($extraBedQuery, 0);
$extraBedOptions = '<option value="">-- Select Age Group --</option>';
while ($row = mysqli_fetch_assoc($extraBedQuery)) {
    $extraBedOptions .= '<option value="'.htmlspecialchars($row['id']).'">'.htmlspecialchars($row['age_group']).' (₹'.$row['extra_price'].')</option>';
}
?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const roomInput = document.getElementById('room_id');
  const checkIn = document.getElementById('check_in');
  const checkOut = document.getElementById('check_out');
  const adults = document.getElementById('guests');
  const children = document.getElementById('children');
  const totalPrice = document.getElementById('total_price');
  const msg = document.getElementById('availabilityMessage');
  const submitBtn = document.getElementById('submitBooking');
  const capacityMessage = document.getElementById('capacityMessage');
  const extraBedContainer = document.getElementById('extraBedContainer');

  // Get total available rooms
$totalRoomResult = mysqli_query($conn, "SELECT total_rooms FROM rooms WHERE id = $room_id");
$row = mysqli_fetch_assoc($totalRoomResult);
$totalRooms = $row['total_rooms'] ?? 0;

// Count already booked rooms for that date range
$bookedResult = mysqli_query($conn, "
  SELECT SUM(no_of_rooms) AS booked_rooms
  FROM bookings
  WHERE room_id = $room_id
    AND (
      ('$check_in' BETWEEN check_in AND DATE_SUB(check_out, INTERVAL 1 DAY))
      OR
      ('$check_out' BETWEEN DATE_ADD(check_in, INTERVAL 1 DAY) AND check_out)
      OR
      (check_in <= '$check_in' AND check_out >= '$check_out')
    )
");
$bookedData = mysqli_fetch_assoc($bookedResult);
$bookedRooms = $bookedData['booked_rooms'] ?? 0;

$availableRooms = max($totalRooms - $bookedRooms, 0);

echo json_encode(['available_rooms' => $availableRooms]);

  let roomCapacity = <?= $room_capacity ?>;

  function populate(select, max) {
    const prev = parseInt(select.value) || 0;
    select.innerHTML = '';
    for (let i = 0; i <= max; i++) {
      let opt = document.createElement('option');
      opt.value = i; opt.textContent = i;
      select.appendChild(opt);
    }
    select.value = prev <= max ? prev : max;
  }

  function showCapacity() {
    const a = +adults.value, c = +children.value;
    const rem = roomCapacity - (a + c);
    capacityMessage.textContent = `Remaining capacity: ${rem >= 0 ? rem : 0}`;
    capacityMessage.style.color = rem < 0 ? 'red' : 'green';
    generateExtra(c);
    calculatePrice();
  }

  adults.addEventListener('change', () => { populate(children, roomCapacity - +adults.value); showCapacity(); });
  children.addEventListener('change', () => { populate(adults, roomCapacity - +children.value); showCapacity(); });

  function generateExtra(count) {
    extraBedContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      const div = document.createElement('div');
      div.className = 'form-group';
      div.innerHTML = `<label>Extra Bed Age Group ${i+1}</label>
        <select name="extra_beds[]" class="form-control extra-bed">
          <?= addslashes($extraBedOptions) ?>
        </select>`;
      extraBedContainer.appendChild(div);
    }
  }

  function calculatePrice() {
    const form = new FormData(document.getElementById('bookingForm'));
    fetch('calculatePrice.php', { method:'POST', body: form })
      .then(r => r.text()).then(txt => {
        totalPrice.value = txt;
        updateSubmitState();
      }).catch(_=> totalPrice.value = '');
  }

  function checkAvailability() {
  const roomId = roomSelect.value;
  const checkIn = checkInInput.value;
  const checkOut = checkOutInput.value;

  if (roomId && checkIn && checkOut) {
    fetch(`ajaxCheckAvailability.php?room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}`)
      .then(res => res.json())
      .then(data => {
        const availabilityText = document.getElementById('availabilityText');
        if (data.available) {
          availabilityText.textContent = data.message;
          availabilityText.style.color = 'green';
        } else {
          availabilityText.textContent = data.message;
          availabilityText.style.color = 'red';
        }
      });
  }
}


  function updateSubmitState() {
    submitBtn.disabled = !(totalPrice.value && msg.textContent.includes('available'));
  }

  [roomInput, checkIn, checkOut].forEach(el=> el.addEventListener('change', checkAvailability));
  document.querySelectorAll('.meal-plan').forEach(cb=> cb.addEventListener('change', calculatePrice));
  document.querySelectorAll('form#bookingForm select').forEach(el => el.addEventListener('change', updateSubmitState));

  // Init
  populate(adults, roomCapacity);
  populate(children, roomCapacity);
  showCapacity();
  if (checkIn.value && checkOut.value && roomInput.value) checkAvailability();
});
</script>
