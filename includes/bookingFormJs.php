<script>
const allRooms = <?= json_encode($roomAvailabilityData) ?>;
let roomSelectCount = 1;

document.addEventListener("DOMContentLoaded", () => {
  const dynamicFields = document.getElementById("dynamicRoomFields");
  const guests = document.getElementById("guests");
  const children = document.getElementById("children");
  const totalPriceField = document.getElementById("total_price");
  const noOfRoomsSelect = document.getElementById("no_of_rooms");
  const submitBtn = document.getElementById("submitBooking");
  const capacityMessage = document.getElementById("capacityMessage");
  const totalGuestsField = document.getElementById("total_guests");
  const checkInField = document.getElementById("check_in");
  const checkOutField = document.getElementById("check_out");
  const extraBedContainer = document.getElementById("extraBedContainer");

  const extraBedOptions = `<?= addslashes('<option value="">-- Select Age Group --</option>') ?>
    <?php while ($row = mysqli_fetch_assoc($extraBedQuery)): ?>
      <?= addslashes('<option value="' . $row['id'] . '">' . $row['age_group'] . ' (₹' . $row['extra_price'] . ')</option>') ?>
    <?php endwhile; ?>`;

  const defaultRoomCount = parseInt(noOfRoomsSelect.dataset.default) || 1;

  for (let i = 1; i <= 5; i++) {
    let opt = document.createElement("option");
    opt.value = i;
    opt.textContent = `${i} Room${i > 1 ? 's' : ''}`;
    if (i === defaultRoomCount) opt.selected = true;
    noOfRoomsSelect.appendChild(opt);
  }

  renderRoomSelectors(defaultRoomCount);

  document.addEventListener("change", (e) => {
    if (
      e.target.classList.contains("dynamic-room-select") ||
      e.target.classList.contains("extra-bed") ||
      e.target.classList.contains("meal-plan")
    ) {
      updateCapacityNotice();
    }
  });

  noOfRoomsSelect.addEventListener("change", () => {
    renderRoomSelectors(parseInt(noOfRoomsSelect.value));
  });

  guests.addEventListener("input", updateCapacityNotice);
  children.addEventListener("input", () => {
    updateCapacityNotice();
    generateExtraBedFields();
  });

  checkInField.addEventListener("change", () => {
    const checkIn = new Date(checkInField.value);
    if (isNaN(checkIn)) return;
    checkIn.setDate(checkIn.getDate() + 1);
    checkOutField.value = checkIn.toISOString().split('T')[0];
  });

  function getRoomUsage() {
    const usage = {};
    document.querySelectorAll(".dynamic-room-select").forEach(sel => {
      const roomId = sel.value;
      if (roomId) {
        usage[roomId] = (usage[roomId] || 0) + 1;
      }
    });
    return usage;
  }

  function renderRoomSelectors(count) {
    dynamicFields.innerHTML = "";
    const usageMap = getRoomUsage();

    for (let i = 0; i < count; i++) {
      const wrapper = document.createElement("div");
      wrapper.className = "form-group";

      const label = document.createElement("label");
      label.textContent = `Select Room ${i + 1}:`;

      const select = document.createElement("select");
      select.name = "selected_rooms[]";
      select.className = "form-control dynamic-room-select mb-2";
      select.required = true;

      const tabUID = `room${i + 1}_${Date.now()}`;
      const currentUsage = getRoomUsage();

      allRooms.forEach(r => {
        const alreadyUsed = currentUsage[r.id] || 0;
        const remaining = r.available - alreadyUsed;
        const option = document.createElement("option");
        option.value = r.id;
        option.textContent = `${r.name} (₹${r.price}) – Room Capacity: ${r.capacity}, Available Rooms: ${remaining}`;
        option.setAttribute("data-image", r.image);
        if (remaining <= 0) option.disabled = true;
        select.appendChild(option);
      });

      const preview = document.createElement("div");
      preview.className = "room-card border rounded p-3 mb-4 bg-light";

      select.addEventListener("change", () => {
        updateRoomCard(select.value, preview, tabUID);
        updateCapacityNotice();
        generateExtraBedFields();
      });

      select.dispatchEvent(new Event("change"));
      wrapper.appendChild(label);
      wrapper.appendChild(select);
      wrapper.appendChild(preview);
      dynamicFields.appendChild(wrapper);
    }

    updateCapacityNotice();
  }

  function updateRoomCard(roomId, container, tabUID) {
    const room = allRooms.find(r => r.id == roomId);
    if (!room) return;

    const tabGroup = `tab_${tabUID}`;

    const amenitiesBadges = room.amenities && room.amenities.length
      ? room.amenities.map(a => {
          const icon = a.icon_class || 'bi-check-circle';
          return `<span class="badge bg-light text-dark border me-1 mb-1">
            <i class="bi ${icon} me-1"></i> ${a.name}
          </span>`;
        }).join('')
      : `<span class="text-muted">No amenities listed.</span>`;

    const photosCarousel = room.photos.length > 0
      ? `
      <div id="carousel${tabGroup}" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          ${room.photos.map((p, i) => `
            <div class="carousel-item ${i === 0 ? 'active' : ''}">
              <img src="${p}" class="d-block w-100" alt="Room photo" style="height:300px; object-fit:cover;">
            </div>
          `).join('')}
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carousel${tabGroup}" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carousel${tabGroup}" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>` : `<p>No photos available.</p>`;

    container.innerHTML = `
      <div class="d-flex gap-3">
        <img src="${room.image}" alt="Room Image" style="height:130px; width:200px; object-fit:cover;" class="rounded shadow-sm">
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-1">${room.name}</h5>
          <p class="mb-1"><strong>Price:</strong> ₹${room.price} / Night</p>
          <p class="mb-1"><strong>Room Capacity:</strong> ${room.capacity}</p>
          <p class="mb-1"><strong>Available Rooms:</strong> ${room.available}</p>

          <ul class="nav nav-tabs mt-3" id="${tabGroup}" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#${tabGroup}_rates">Description</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${tabGroup}_amenities">Amenities</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#${tabGroup}_photos">Photos</a></li>
          </ul>

          <div class="tab-content border p-3">
            <div class="tab-pane fade show active" id="${tabGroup}_rates">
              <p>${room.description || 'No rate description.'}</p>
            </div>
            <div class="tab-pane fade" id="${tabGroup}_amenities">
  ${
    room.amenities && room.amenities.length > 0
    ? room.amenities.map(a => `
      <span class="badge bg-light text-dark border me-1 mb-1">
        <i class="bi ${a.icon_class || 'bi-check-circle'} me-1"></i>
        ${a.name}
      </span>`).join('')
    : '<p class="text-muted">No amenities listed.</p>'
  }
</div>

            <div class="tab-pane fade" id="${tabGroup}_photos">
              ${photosCarousel}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function updateCapacityNotice() {
    let totalGuests = parseInt(guests.value || 0) + parseInt(children.value || 0);
    let totalCapacity = 0;

    document.querySelectorAll(".dynamic-room-select").forEach(select => {
      const room = allRooms.find(r => r.id == select.value);
      if (room) totalCapacity += room.capacity;
    });

    totalGuestsField.value = totalGuests;

    const diff = totalCapacity - totalGuests;
    capacityMessage.textContent = diff < 0
      ? `⚠️ Capacity exceeded by ${Math.abs(diff)} guests.`
      : `Remaining capacity: ${diff}`;
    capacityMessage.style.color = diff < 0 ? 'red' : 'green';

    submitBtn.disabled = diff < 0 || totalCapacity === 0;
    calculatePrice();
  }

  function generateExtraBedFields() {
    const numChildren = parseInt(children.value || 0);
    extraBedContainer.innerHTML = '';
    for (let i = 0; i < numChildren; i++) {
      const div = document.createElement("div");
      div.className = "form-group";
      div.innerHTML = `<label>Extra Bed Age Group (Child ${i + 1}):</label>
        <select name="extra_beds[]" class="form-control mb-2" required>
          ${extraBedOptions}
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
    .then(txt => totalPriceField.value = "₹" + txt)
    .catch(() => totalPriceField.value = "Error");
  }
});
</script>
