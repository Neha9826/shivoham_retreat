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

$roomAvailabilityData = [];
$checkInEscaped = mysqli_real_escape_string($conn, $check_in);
$checkOutEscaped = mysqli_real_escape_string($conn, $check_out);

// Fetch all room data including first image
$roomInfoQuery = mysqli_query($conn, "
  SELECT 
    r.id, r.room_name, r.price_per_night, r.room_capacity, r.total_rooms, r.description,
    (SELECT image_path FROM room_images WHERE room_id = r.id LIMIT 1) AS image
  FROM rooms r
");

while ($r = mysqli_fetch_assoc($roomInfoQuery)) {
    $roomId = (int)$r['id'];
    $capacity = (int)$r['room_capacity'];
    $totalRooms = (int)$r['total_rooms'];
    $price = (float)$r['price_per_night'];
    $image = $r['image'] ? 'admin/' . $r['image'] : 'assets/img/no-image.png';
    $desc = $r['description'];

    // Get booked rooms
    $bookingQuery = "
        SELECT SUM(no_of_rooms) as booked
        FROM bookings
        WHERE room_id = $roomId
          AND (
            ('$checkInEscaped' BETWEEN check_in AND DATE_SUB(check_out, INTERVAL 1 DAY)) OR
            ('$checkOutEscaped' BETWEEN DATE_ADD(check_in, INTERVAL 1 DAY) AND check_out) OR
            (check_in <= '$checkInEscaped' AND check_out >= '$checkOutEscaped')
          )
    ";
    $bookedResult = mysqli_query($conn, $bookingQuery);
    $bookedRooms = mysqli_fetch_assoc($bookedResult)['booked'] ?? 0;
    $available = max(0, $totalRooms - (int)$bookedRooms);

    // Fetch amenities
    $amenitiesResult = mysqli_query($conn, "
        SELECT a.name FROM amenities a
        INNER JOIN room_amenities ra ON ra.amenity_id = a.id
        WHERE ra.room_id = $roomId
    ");
    $amenities = [];
    while ($am = mysqli_fetch_assoc($amenitiesResult)) {
        $amenities[] = $am['name'];
    }

    // Fetch photos
    $photos = [];
    $photosResult = mysqli_query($conn, "SELECT image_path FROM room_images WHERE room_id = $roomId");
    while ($p = mysqli_fetch_assoc($photosResult)) {
        $photos[] = 'admin/uploads/rooms/' . $p['image_path'];
    }

    $roomAvailabilityData[] = [
        'id' => $roomId,
        'name' => $r['room_name'],
        'price' => $price,
        'capacity' => $capacity,
        'available' => $available,
        'description' => $desc,
        'image' => $image,
        'amenities' => $amenities,
        'photos' => $photos
    ];
}
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-11">
      <div class="card shadow-sm border-0">
        <div class="card-header text-center bg-light">
          <h4 class="mb-0">Book Your Stay at Shivoham Retreat</h4>
        </div>
        <div class="card-body booking-section">

          <!-- ✅ Room Card Preview (dynamically filled) -->
          <div id="selectedRoomCard" class="mb-4">
            <!-- will be populated dynamically -->
          </div>

          <!-- ✅ Booking Form Start -->
          <form id="bookingForm" method="POST" action="submitBooking.php">
            <!-- rest of the form continues in next part -->

            <!-- Guests, dates -->
<div class="form-row">
  <div class="form-group col-md-4">
    <label>Check-in:</label>
    <input type="date" name="check_in" id="check_in" class="form-control" value="<?= htmlspecialchars($check_in) ?>" required>
  </div>
  <div class="form-group col-md-4">
    <label>Check-out:</label>
    <input type="date" name="check_out" id="check_out" class="form-control" value="<?= htmlspecialchars($check_out) ?>" required>
  </div>
</div>

<div class="form-row">
  <div class="form-group col-md-6">
    <label>No. of Adults:</label>
    <input type="number" name="guests" id="guests" class="form-control" min="1" max="20" value="<?= htmlspecialchars($guests) ?>" required>
  </div>
  <div class="form-group col-md-6">
    <label>No. of Children:</label>
    <input type="number" name="children" id="children" class="form-control" min="0" max="10" value="<?= htmlspecialchars($children) ?>" required>
    <p id="capacityMessage" style="font-weight:bold; color:green;"></p>
  </div>
</div>

<div class="form-group">
  <label>Number of Rooms:</label>
  <select name="no_of_rooms" id="no_of_rooms" class="form-control" required></select>
  <div id="dynamicRoomFields" class="my-2"></div>
  <div id="addRoomBtnContainer" class="my-2"></div>
</div>

<div class="form-group">
  <label>Extra Bed Age Group:</label>
  <div id="extraBedContainer"></div>
</div>

<div class="form-group">
  <label>Meal Plan:</label>
  <?php mysqli_data_seek($mealPlanQuery, 0); ?>
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
<div class="form-group"><label>Your Name:</label><input type="text" name="name" required class="form-control"></div>
<div class="form-row">
  <div class="form-group col-md-6"><label>Email:</label><input type="email" name="email" required class="form-control"></div>
  <div class="form-group col-md-6"><label>Phone:</label><input type="tel" name="phone" required class="form-control"></div>
</div>

<div class="form-group"><label>Total Price (₹):</label><input type="text" name="total_price" id="total_price" class="form-control" readonly></div>
<input type="hidden" name="total_guests" id="total_guests" value="">

<div id="availabilityMessage"></div>
<button type="submit" id="submitBooking" class="btn btn-primary" disabled>Submit Booking</button>
</form>
</div>
</div>
</div>
</div>
</div>

<script>
const allRooms = <?= json_encode($roomAvailabilityData) ?>;
let roomSelectCount = 1;

document.addEventListener("DOMContentLoaded", () => {
  const dynamicFields = document.getElementById("dynamicRoomFields");
  const selectedRoomCard = document.getElementById("selectedRoomCard");
  const guests = document.getElementById("guests");
  const children = document.getElementById("children");
  const totalPriceField = document.getElementById("total_price");
  const noOfRoomsSelect = document.getElementById("no_of_rooms");
  const submitBtn = document.getElementById("submitBooking");
  const capacityMessage = document.getElementById("capacityMessage");
  const totalGuestsField = document.getElementById("total_guests");
  const addRoomBtnContainer = document.getElementById("addRoomBtnContainer");

  // Render rooms
  for (let i = 1; i <= 5; i++) {
    let opt = document.createElement("option");
    opt.value = i;
    opt.textContent = `${i} Room${i > 1 ? 's' : ''}`;
    noOfRoomsSelect.appendChild(opt);
  }

  noOfRoomsSelect.value = "1";
  renderRoomSelectors(1);

  guests.addEventListener("input", updateCapacityNotice);
  children.addEventListener("input", updateCapacityNotice);
  noOfRoomsSelect.addEventListener("change", () => {
    const count = parseInt(noOfRoomsSelect.value);
    renderRoomSelectors(count);
  });

  function renderRoomSelectors(count) {
    dynamicFields.innerHTML = "";
    for (let i = 0; i < count; i++) {
      const wrapper = document.createElement("div");
      wrapper.className = "form-group";

      const label = document.createElement("label");
      label.textContent = `Select Room ${i + 1}:`;

      const select = document.createElement("select");
      select.name = "selected_rooms[]";
      select.className = "form-control dynamic-room-select mb-2";
      select.required = true;

      allRooms.forEach(r => {
        const option = document.createElement("option");
        option.value = r.id;
        option.textContent = `${r.name} (₹${r.price}) – Room Capacity: ${r.capacity}, Available Rooms: ${r.available}`;
        option.setAttribute("data-image", r.image);
        select.appendChild(option);
      });

      const preview = document.createElement("div");
      preview.className = "room-card border rounded p-3 mb-4 bg-light";
      select.addEventListener("change", () => updateRoomCard(select.value, preview));
      select.dispatchEvent(new Event("change"));

      wrapper.appendChild(label);
      wrapper.appendChild(select);
      wrapper.appendChild(preview);

      dynamicFields.appendChild(wrapper);
    }
    updateCapacityNotice();
  }

  function updateRoomCard(roomId, container) {
    const room = allRooms.find(r => r.id == roomId);
    if (!room) return;

    container.innerHTML = `
      <div class="d-flex gap-3">
        <img src="${room.image}" alt="Room Image" style="height:130px; width:200px; object-fit:cover;" class="rounded shadow-sm">
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-1">${room.name}</h5>
          <p class="mb-1"><strong>Price:</strong> ₹${room.price} / Night</p>
          <p class="mb-1"><strong>Room Capacity:</strong> ${room.capacity}</p>
          <p class="mb-1"><strong>Available Rooms:</strong> ${room.available}</p>

          <ul class="nav nav-tabs mt-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#rates${room.id}">Rates</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#amenities${room.id}">Amenities</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#photos${room.id}">Photos</a></li>
          </ul>

          <div class="tab-content border p-3">
            <div class="tab-pane fade show active" id="rates${room.id}">
              <p>${room.description || 'No rate description.'}</p>
            </div>
            <div class="tab-pane fade" id="amenities${room.id}">
              <ul class="mb-0">${room.amenities.map(a => `<li>${a}</li>`).join('') || '<li>No amenities</li>'}</ul>
            </div>
            <div class="tab-pane fade" id="photos${room.id}">
              <div class="d-flex flex-wrap gap-2">
                ${room.photos.map(p => `<img src="${p}" class="rounded" style="width:80px; height:60px; object-fit:cover;">`).join('')}
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function updateCapacityNotice() {
    const guestsVal = parseInt(guests.value || 0);
    const childrenVal = parseInt(children.value || 0);
    const totalGuests = guestsVal + childrenVal;
    totalGuestsField.value = totalGuests;

    let totalCapacity = 0;
    document.querySelectorAll(".dynamic-room-select").forEach(select => {
      const room = allRooms.find(r => r.id == select.value);
      if (room) totalCapacity += room.capacity;
    });

    const diff = totalCapacity - totalGuests;
    capacityMessage.textContent = diff < 0
      ? `⚠️ Capacity exceeded by ${Math.abs(diff)} guests.`
      : `Remaining capacity: ${diff}`;
    capacityMessage.style.color = diff < 0 ? 'red' : 'green';

    submitBtn.disabled = diff < 0;
    calculatePrice();
  }

  function calculatePrice() {
    const form = new FormData(document.getElementById("bookingForm"));
    fetch("calculatePrice.php", {
      method: "POST",
      body: form
    })
      .then(r => r.text())
      .then(txt => totalPriceField.value = txt)
      .catch(() => totalPriceField.value = "Error");
  }
});
</script>
