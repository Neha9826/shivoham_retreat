<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm border-0">
        <div class="card-body booking-section">
          <form id="bookingForm" method="POST" action="submitBooking.php">

            <!-- Room selector area -->
            <div class="form-group">
              <label>Number of Rooms:</label>
              <select name="no_of_rooms" id="no_of_rooms" class="form-control" required></select>
              <p id="roomAvailabilityMsg" style="font-weight:bold; color:#d9534f;"></p>
              <div id="dynamicRoomFields"></div>
              <div id="addRoomBtnContainer" class="my-2"></div>
            </div>

            <!-- Dates and guests -->
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

            <!-- Extra bed -->
            <div class="form-group">
              <label>Extra Bed Age Group:</label>
              <div id="extraBedContainer"></div>
            </div>

            <!-- Meal Plan -->
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

            <!-- User Info -->
            <div class="form-group"><label>Your Name:</label><input type="text" name="name" required class="form-control"></div>
            <div class="form-row">
              <div class="form-group col-md-6"><label>Email:</label><input type="email" name="email" required class="form-control"></div>
              <div class="form-group col-md-6"><label>Phone:</label><input type="tel" name="phone" required class="form-control"></div>
            </div>

            <!-- Price and submit -->
            <div class="form-group"><label>Total Price (₹):</label><input type="text" name="total_price" id="total_price" class="form-control" readonly></div>
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
    $extraBedOptions .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['age_group']) . ' (₹' . $row['extra_price'] . ')</option>';
}
?>

<script>
const allRooms = <?= json_encode($roomAvailabilityData) ?>;

document.addEventListener("DOMContentLoaded", () => {
  const guests = document.getElementById("guests");
  const children = document.getElementById("children");
  const extraBedContainer = document.getElementById("extraBedContainer");
  const totalPriceField = document.getElementById("total_price");
  const noOfRoomsSelect = document.getElementById("no_of_rooms");
  const dynamicRoomsDiv = document.getElementById("dynamicRoomFields");
  const capacityMessage = document.getElementById("capacityMessage");

  const totalGuestsField = document.createElement("input");
  totalGuestsField.type = "hidden";
  totalGuestsField.name = "total_guests";
  document.getElementById("bookingForm").appendChild(totalGuestsField);

  const addRoomBtnContainer = document.getElementById("addRoomBtnContainer");

  // Populate number of rooms
  const maxRooms = allRooms.reduce((sum, r) => sum + r.available, 0);
  for (let i = 1; i <= maxRooms; i++) {
    const opt = document.createElement("option");
    opt.value = i;
    opt.textContent = `${i} Room${i > 1 ? 's' : ''}`;
    noOfRoomsSelect.appendChild(opt);
  }

  // Initial state
  noOfRoomsSelect.value = "1";
  renderRoomSelectors(1);
  updateCapacityNotice();

  // Listeners
  [guests, children, noOfRoomsSelect].forEach(el => {
    el.addEventListener("input", () => {
      updateCapacityNotice();
      generateExtraBeds(Number(children.value) || 0);
    });
  });

  document.addEventListener("change", (e) => {
    if (
      e.target.classList.contains("dynamic-room-select") ||
      e.target.classList.contains("extra-bed") ||
      e.target.classList.contains("meal-plan")
    ) {
      updateCapacityNotice();
    }
  });

  function getRoomUsageMap() {
    const usage = {};
    document.querySelectorAll(".dynamic-room-select").forEach(sel => {
      const val = sel.value;
      if (val) usage[val] = (usage[val] || 0) + 1;
    });
    return usage;
  }

  function renderRoomSelectors(count) {
    dynamicRoomsDiv.innerHTML = "";

    for (let i = 0; i < count; i++) {
      const wrapper = document.createElement("div");
      wrapper.className = "form-group";

      const label = document.createElement("label");
      label.textContent = `Select Room ${i + 1}:`;

      const select = document.createElement("select");
      select.name = "selected_rooms[]";
      select.className = "form-control dynamic-room-select mb-1";
      select.required = true;

      const roomUsage = getRoomUsageMap();
      allRooms.forEach(room => {
        const used = roomUsage[room.id] || 0;
        const option = document.createElement("option");
        option.value = room.id;
        option.textContent = `${room.name} (₹${room.price}) – Room Capacity: ${room.capacity}, Available Rooms: ${room.available}`;
        option.setAttribute("data-image", room.image || "");
        if (used >= room.available) option.disabled = true;
        select.appendChild(option);
      });

      const preview = document.createElement("div");
      preview.className = "room-preview mb-2";

      select.addEventListener("change", () => {
        const selectedOption = select.options[select.selectedIndex];
        const imgSrc = selectedOption.getAttribute("data-image");
        const labelText = selectedOption.textContent;
        preview.innerHTML = imgSrc
          ? `<img src="${imgSrc}" alt="Room Image" style="max-height:60px; margin-right:10px;"> <strong>${labelText}</strong>`
          : `<strong>${labelText}</strong>`;
      });

      select.dispatchEvent(new Event("change"));

      wrapper.appendChild(label);
      wrapper.appendChild(select);
      wrapper.appendChild(preview);

      if (i > 0) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = "Remove";
        btn.className = "btn btn-sm btn-danger mt-1";
        btn.onclick = () => {
          wrapper.remove();
          noOfRoomsSelect.value = document.querySelectorAll(".dynamic-room-select").length;
          updateCapacityNotice();
        };
        wrapper.appendChild(btn);
      }

      dynamicRoomsDiv.appendChild(wrapper);
    }

    attachRoomSelectListeners();
    updateCapacityNotice();
  }

  function attachRoomSelectListeners() {
    document.querySelectorAll(".dynamic-room-select").forEach(select => {
      select.removeEventListener("change", updateCapacityNotice);
      select.addEventListener("change", updateCapacityNotice);
    });
  }

  function updateCapacityNotice() {
    const totalGuests = parseInt(guests.value || 0) + parseInt(children.value || 0);
    totalGuestsField.value = totalGuests;

    let totalCapacity = 0;
    document.querySelectorAll(".dynamic-room-select").forEach(select => {
      const room = allRooms.find(r => r.id == select.value);
      if (room) totalCapacity += room.capacity;
    });

    const remaining = totalCapacity - totalGuests;

    if (remaining < 0) {
      capacityMessage.textContent = `⚠️ Capacity exceeded by ${Math.abs(remaining)} guests.`;
      capacityMessage.style.color = "red";
      renderAddRoomButton();
    } else {
      capacityMessage.textContent = `Remaining capacity: ${remaining}`;
      capacityMessage.style.color = "green";
      addRoomBtnContainer.innerHTML = '';
    }

    calculatePrice();

    document.getElementById("submitBooking").disabled = !(totalPriceField.value && !capacityMessage.textContent.includes("exceeded"));
  }

  function renderAddRoomButton() {
    const currentCount = document.querySelectorAll(".dynamic-room-select").length;
    const maxAvailable = allRooms.reduce((sum, r) => sum + r.available, 0);

    if (currentCount >= maxAvailable) {
      addRoomBtnContainer.innerHTML = '<p class="text-danger">No more rooms available.</p>';
      return;
    }

    addRoomBtnContainer.innerHTML = '';
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = "➕ Add Another Room";
    btn.className = "btn btn-warning btn-sm mt-2";
    btn.onclick = () => {
      renderRoomSelectors(currentCount + 1);
      noOfRoomsSelect.value = currentCount + 1;
    };
    addRoomBtnContainer.appendChild(btn);
  }

  function generateExtraBeds(count) {
    extraBedContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      const div = document.createElement("div");
      div.className = "form-group";
      div.innerHTML = `<label>Extra Bed Age Group ${i + 1}</label>
        <select name="extra_beds[]" class="form-control extra-bed">
          <?= addslashes($extraBedOptions) ?>
        </select>`;
      extraBedContainer.appendChild(div);
    }
  }

  function calculatePrice() {
    const form = new FormData(document.getElementById("bookingForm"));
    fetch("calculatePrice.php", {
      method: "POST",
      body: form
    })
      .then(r => r.text())
      .then(txt => {
        totalPriceField.value = txt;
      })
      .catch(() => {
        totalPriceField.value = "Error";
      });
  }
});
</script>
