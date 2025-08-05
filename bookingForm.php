<?php
include 'db.php';
$room_id = $_GET['room_id'] ?? '';
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$no_of_rooms = $_GET['no_of_rooms'] ?? $_SESSION['num_rooms'] ?? 1;
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
        $photos[] = 'admin/' . $p['image_path'];
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
                <div class="form-group col-md-4">
                  <label>Number of Rooms:</label>
                  <!-- <select name="no_of_rooms" id="no_of_rooms" class="form-control" value="<?= htmlspecialchars($no_of_rooms) ?>" required></select> -->
                  <select name="no_of_rooms" id="no_of_rooms" class="form-control" data-default="<?= htmlspecialchars($no_of_rooms) ?>" required></select>
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

<?php include 'includes/bookingFormJs.php'; ?>