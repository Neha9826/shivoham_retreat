<?php
include 'db.php';
$extraBedQuery = mysqli_query($conn, "SELECT * FROM extra_bed_rates");
$mealPlanQuery = mysqli_query($conn, "SELECT * FROM meal_plan");
$roomsQuery = mysqli_query($conn, "SELECT id, room_name, price_per_night, total_rooms FROM rooms");

$selectedRoomId = isset($_GET['room_id']) ? $_GET['room_id'] : '';

require 'db.php'; // or your connection file
$room_id = $_GET['room_id'] ?? null;
$total_rooms = 10; // default value in case query fails

if ($room_id) {
    $stmt = $conn->prepare("SELECT total_rooms FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($roomData = $result->fetch_assoc()) {
        $total_rooms = $roomData['total_rooms'];
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
          <form id="bookingForm" method="POST">

            <!-- Select Room -->
            <div class="form-group">
  <label>Select Room:</label>
  <select id="room_id_display" class="form-control" disabled>
    <!-- <option value="">-- Select Room --</option> -->
    <?php while ($r = mysqli_fetch_assoc($roomsQuery)): ?>
      <option value="<?= $r['id'] ?>"
              data-price="<?= $r['price_per_night'] ?>"
              data-available="<?= $r['total_rooms'] ?>"
              <?= ($r['id'] == $selectedRoomId) ? 'selected' : '' ?>>
        <?= htmlspecialchars($r['room_name']) ?> – ₹<?= $r['price_per_night'] ?>/night
      </option>
    <?php endwhile; ?>
  </select>
  <!-- Hidden field to actually submit room_id value -->
  <select name="room_id" id="room_id" class="form-control d-none">
    <option value="">-- Select Room --</option>
    <?php
    mysqli_data_seek($roomsQuery, 0); // reset again
    while ($r = mysqli_fetch_assoc($roomsQuery)): ?>
      <option value="<?= $r['id'] ?>"
              data-price="<?= $r['price_per_night'] ?>"
              data-available="<?= $r['total_rooms'] ?>"
              <?= ($r['id'] == $selectedRoomId) ? 'selected' : '' ?>>
        <?= htmlspecialchars($r['room_name']) ?>
      </option>
    <?php endwhile; ?>
  </select>
</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="no_of_rooms">Number of Rooms:</label>
                    <select name="no_of_rooms" id="no_of_rooms" class="form-control" onchange="calculateTotal()">
                        <?php for ($i = 1; $i <= $total_rooms; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

              <div class="form-group col-md-4">
                <label>Check-in:</label>
                <input type="date" name="check_in" id="check_in" required class="form-control">
              </div>
              <div class="form-group col-md-4">
                <label>Check-out:</label>
                <input type="date" name="check_out" id="check_out" required class="form-control">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>No. of Adults:</label>
                <select name="adults" id="adults" required class="form-control">
                  <option value="">--</option>
                  <?php for ($i=1; $i<=5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label>No. of Children:</label>
                <select name="children" id="children" class="form-control">
                  <option value="0">0</option>
                  <?php for ($i=1; $i<=5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </div>
            </div>

            <!-- Extra Bed Age Group - Dynamically generated -->
            <div class="form-group">
                <label>Extra Bed Age Group:</label>
                <div id="extraBedContainer"></div>
            </div>
            <div class="form-group">
              <label>Meal Plan:</label>
              <?php mysqli_data_seek($mealPlanQuery,0);
              while ($mp = mysqli_fetch_assoc($mealPlanQuery)): ?>
                <div class="form-check">
                  <input class="form-check-input meal-plan" type="checkbox"
                         name="meal_plan_id[]" value="<?= $mp['id'] ?>"
                         data-price="<?= $mp['price'] ?>" id="meal<?= $mp['id'] ?>">
                  <label class="form-check-label" for="meal<?= $mp['id'] ?>">
                    <?= htmlspecialchars($mp['name']) ?> (₹<?= $mp['price'] ?>)
                  </label>
                </div>
              <?php endwhile; ?>
            </div>

            <hr>

            <div class="form-group">
              <label>Your Name:</label>
              <input type="text" name="name" required class="form-control">
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Email:</label>
                <input type="email" name="email" required class="form-control">
              </div>
              <div class="form-group col-md-6">
                <label>Phone:</label>
                <input type="tel" name="phone" required class="form-control">
              </div>
            </div>

            <div class="form-group">
              <label>Total Price (₹):</label>
              <input type="text" name="total_price" id="total_price" class="form-control" readonly>
            </div>
            <button type="submit" class="btn btn-info btn-block">Submit Booking</button>
          </form>

          <div id="responseMessage" class="mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// Prepare JS-friendly string for dropdown options
mysqli_data_seek($extraBedQuery, 0);
$extraBedOptions = '<option value="">-- Select Age Group --</option>';
while ($row = mysqli_fetch_assoc($extraBedQuery)) {
    $value = htmlspecialchars($row['id']);
    $label = htmlspecialchars($row['age_group']) . ' (₹' . $row['extra_price'] . ')';
    $extraBedOptions .= '<option value="' . $value . '">' . $label . '</option>';
}
?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const checkInInput = document.getElementById("check_in");
    const checkOutInput = document.getElementById("check_out");
    const roomInput = document.getElementById("room_id");
    const roomsCountInput = document.getElementById("no_of_rooms");
    const adultsInput = document.getElementById("adults");
    const childrenInput = document.getElementById("children");
    const extraBedContainer = document.getElementById("extraBedContainer");
    const mealPlanCheckboxes = document.querySelectorAll('input[name="meal_plan_id[]"]');

    const extraBedOptionsHTML = `<?= $extraBedOptions ?>`;

    function calculateTotal() {
    const checkIn = new Date(checkInInput.value);
    const checkOut = new Date(checkOutInput.value);

    if (isNaN(checkIn.getTime()) || isNaN(checkOut.getTime())) {
        return; // Invalid dates, don't proceed
    }

    const timeDiff = checkOut - checkIn;
    const nights = Math.max(1, Math.ceil(timeDiff / (1000 * 60 * 60 * 24)));

    const roomId = roomInput.value;
    const roomsCount = parseInt(roomsCountInput.value) || 1;

    const formData = new FormData();
    formData.append('room_id', roomId);
    formData.append('nights', nights);
    formData.append('no_of_rooms', roomsCount);
    formData.append('check_in', checkInInput.value);
    formData.append('check_out', checkOutInput.value);


    // Fix: Use consistent name for extra beds
    const ageGroupSelects = extraBedContainer.querySelectorAll('select.extra-bed-select');
    ageGroupSelects.forEach(select => {
        if (select.value) {
            formData.append('extra_beds[]', select.value);
        }
    });

    // Meal plans
    document.querySelectorAll('input[name="meal_plan_id[]"]:checked').forEach(cb => {
        formData.append('meal_plan_id[]', cb.value);
    });

    fetch('calculatePrice.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(price => {
        document.getElementById('total_price').value = price;
    })
    .catch(err => {
        document.getElementById('total_price').value = "Error";
        console.error("Price calculation failed:", err);
    });
}


    function generateExtraBedAgeDropdowns(count) {
    extraBedContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-group';

        const label = document.createElement('label');
        label.textContent = `Extra Bed Age Group ${i + 1}`;

        const select = document.createElement('select');
        select.className = 'form-control mt-1 extra-bed-select';
        select.name = `extra_beds[]`; // Fix: this must match PHP's expected key
        select.innerHTML = extraBedOptionsHTML;

        wrapper.appendChild(label);
        wrapper.appendChild(select);
        extraBedContainer.appendChild(wrapper);
    }
}

    [checkInInput, checkOutInput, roomInput, roomsCountInput, adultsInput, childrenInput].forEach(input => {
        input.addEventListener('change', () => {
            if (input === childrenInput) {
                const childCount = parseInt(childrenInput.value) || 0;
                generateExtraBedAgeDropdowns(childCount);
            }
            calculateTotal();
        });
    });

    document.querySelectorAll('input[name="meal_plan_id[]"]').forEach(cb => {
        cb.addEventListener('change', calculateTotal);
    });
});
</script>
