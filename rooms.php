<?php
// rooms.php (final consolidated version - restored to original working behavior)
// NOTE: this file expects a mysqli connection $conn. It will include db.php if $conn not defined.
// Make a backup before replacing your original.

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn) || !($conn instanceof mysqli)) {
    include 'db.php';
}

$basePath = ''; // update if your project lives in a subfolder e.g. '/ShivohamRetreat/'

// Prefer values from URL (so new tab / fresh load doesn't get stale session values)
$check_in = (isset($_GET['check_in']) && $_GET['check_in'] !== '') 
            ? trim($_GET['check_in']) 
            : (isset($_SESSION['check_in']) ? trim($_SESSION['check_in']) : '');

$check_out = (isset($_GET['check_out']) && $_GET['check_out'] !== '') 
             ? trim($_GET['check_out']) 
             : (isset($_SESSION['check_out']) ? trim($_SESSION['check_out']) : '');

// numeric fields: prefer URL then session fallback
$no_of_rooms = isset($_GET['no_of_rooms']) ? intval($_GET['no_of_rooms']) : (isset($_SESSION['no_of_rooms']) ? intval($_SESSION['no_of_rooms']) : 1);
$guests      = isset($_GET['guests'])      ? intval($_GET['guests'])      : (isset($_SESSION['guests']) ? intval($_SESSION['guests']) : 2);
$children    = isset($_GET['children'])    ? intval($_GET['children'])    : (isset($_SESSION['num_children']) ? intval($_SESSION['num_children']) : 0);

// ⬇️ ADD THIS RIGHT HERE
if (!isset($_GET['check_in']) && isset($_SESSION['check_in'])) {
    unset(
        $_SESSION['check_in'],
        $_SESSION['check_out'],
        $_SESSION['no_of_rooms'],
        $_SESSION['guests'],
        $_SESSION['num_children']
    );
}

// === Helpers ===
function build_capacity_text($base, $ebCap, $maxEA, $maxEC) {
    $base = (int)$base; $ebCap = (int)$ebCap; $maxEA = (int)$maxEA; $maxEC = (int)$maxEC;
    if ($ebCap <= 0 || ($maxEA <= 0 && $maxEC <= 0)) {
        return "Base {$base}";
    }
    if ($maxEA > 0 && $maxEC > 0) return "Base {$base} + up to {$maxEA} adult(s) and {$maxEC} child(ren)";
    if ($maxEA > 0) return "Base {$base} + up to {$maxEA} adult(s)";
    if ($maxEC > 0) return "Base {$base} + up to {$maxEC} child(ren)";
    return "Base {$base}";
}

function get_standard_room_price($conn, $room_id, $date) {
    if (!$date) return null;
    $dayOfWeek = date('l', strtotime($date));
    $priceColumn = strtolower($dayOfWeek) . '_standard';
    $sql = "SELECT {$priceColumn} FROM room_seasonal_prices
             WHERE room_id = ?
             AND ? BETWEEN start_date AND end_date
             LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("is", $room_id, $date);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    return $row && isset($row[$priceColumn]) ? (float)$row[$priceColumn] : null;
}

/**
 * compute_available_qty
 * - Returns integer >= 0 when check_in/check_out are provided
 * - Returns null when dates are missing (so UI can show neutral placeholder)
 *
 * @return int|null
 */
function compute_available_qty(mysqli $conn, int $room_id, string $check_in, string $check_out, int $total_qty): ?int {
    // if no dates provided, signal "unknown" availability to the UI
    if (!$check_in || !$check_out) return null;

    // 1) Find the maximum number of booked rooms on any single night in the date range
    $sql = "
        SELECT MAX(daily_total) AS max_booked
        FROM (
            SELECT `date`, COALESCE(SUM(booked_rooms),0) AS daily_total
            FROM room_availability
            WHERE room_id = ?
              AND date >= ? AND date < ?
            GROUP BY `date`
        ) AS daily
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // defensive fallback: return full stock if prepare fails
        return $total_qty;
    }
    $stmt->bind_param("iss", $room_id, $check_in, $check_out);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $maxBooked = (int)($res['max_booked'] ?? 0);

    // 2) Compute available quantity: stock minus the busiest night’s usage
    $available = $total_qty - $maxBooked;

    // Don’t allow negative values
    return max(0, $available);
}


// === Fetch rooms from DB ===
$sql = "SELECT r.*,
                (SELECT image_path FROM room_images WHERE room_id = r.id LIMIT 1) AS main_image,
                (SELECT GROUP_CONCAT(a.name, '|', a.icon_class)
                   FROM amenities a
                   JOIN room_amenities ra ON ra.amenity_id = a.id
                  WHERE ra.room_id = r.id) AS amenity_data
        FROM rooms r
        ORDER BY r.id DESC";
$roomResult = $conn->query($sql);

// Process all rooms (we compute server-side availability if dates were provided).
$rooms = [];
if ($roomResult && $roomResult->num_rows > 0) {
    while ($room = $roomResult->fetch_assoc()) {
        $room_id  = (int)$room['id'];
        $total_qty = (int)$room['total_rooms'];

        // Compute availability using helper (server-side initial state).
        $room['available_qty'] = compute_available_qty($conn, $room_id, $check_in, $check_out, $total_qty);

        // Capacity logic (unchanged)
        $adults = (int)$guests;
        $children_5_12 = min((int)$children, (int)$room['max_child_without_bed_5_12']);
        $children_below_5 = max(0, $children - $children_5_12); // not counted in capacity

        $per_room_capacity = (int)$room['base_adults'] + (int)$room['max_extra_with_bed'] + (int)$room['max_child_without_bed_5_12'];
        $total_capacity_allowed = $per_room_capacity * $no_of_rooms;
        $group_size = $adults + $children_5_12;
        $room['is_match'] = ($group_size <= $total_capacity_allowed);

        // Image path fix
        $imagePath = $room['main_image'] ?? 'assets/img/default-room.jpg';
        if (strpos($imagePath, 'admin/') !== 0 && strpos($imagePath, 'assets/') !== 0) {
            $imagePath = 'admin/' . $imagePath;
        }
        $room['main_image'] = $basePath . $imagePath;

        // Amenities
        $amenityList = [];
        if (!empty($room['amenity_data'])) {
            $pairs = explode(',', $room['amenity_data']);
            foreach ($pairs as $pair) {
                $parts = explode('|', $pair);
                $name = $parts[0] ?? '';
                $icon = $parts[1] ?? 'bi-check-circle';
                if ($name) $amenityList[] = ['name' => $name, 'icon' => $icon];
            }
        }
        $room['amenities'] = $amenityList;

        // Price
        $currentDate = date('Y-m-d');
        $price = get_standard_room_price($conn, $room_id, $currentDate);
        $room['price_display'] = $price ?? (float)$room['standard_price'];

        $rooms[] = $room;
    }
}

$roomDataJson = json_encode($rooms);
?>
<!-- HTML / UI -->
<div class="offers_area">
    <div class="container">
        <div class="row room-card">
            <div class="col-xl-12">
                <div class="section_title text-center mb-40">
                    <span>Available Rooms</span>
                    <h3>Our Best Rooms</h3>

                    <!-- Single top-level search form -->
                    <!-- <form id="roomsAvailabilityForm" class="mb-5" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label>Check-in Date:</label>
                                <input type="date" id="check_in" name="check_in" class="form-control" autocomplete="off" required>
                            </div>
                            <div class="col-md-2">
                                <label>Check-out Date:</label>
                                <input type="date" id="check_out" name="check_out" class="form-control" autocomplete="off" required>
                            </div>
                            <div class="col-md-2">
                                <label>No. of Rooms:</label>
                                <input type="number" id="no_of_rooms" name="no_of_rooms" min="1" value="<?= htmlspecialchars($no_of_rooms) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>No. of Adults:</label>
                                <input type="number" id="guests" name="guests" min="1" value="<?= htmlspecialchars($guests) ?>" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>No. of Children:</label>
                                <input type="number" id="num_children" name="num_children" min="0" value="<?= htmlspecialchars($children) ?>" class="form-control">
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary checkAvailabilityBtn">
                                    Check Availability
                                </button>
                            </div>
                        </div>
                    </form> -->

                </div>
            </div>
        </div>

        <div  class="row d-flex justify-content-center">
            <?php foreach ($rooms as $room): ?>
              
                <div class="col-xl-4 col-md-6 mb-4">
                  <a href="room_details.php?room_id=<?= $room['id'] ?>"
                                    data-room-id="<?= $room['id'] ?>">
                    <div style="background-color: #f5f5f5;" class="single_offers card h-100" data-room-id="<?= $room['id'] ?>" style="cursor:pointer;">
                        <img src="<?= htmlspecialchars($room['main_image']) ?>"
                             class="card-img-top" style="height:230px;object-fit:cover;" alt="Room Image">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($room['room_name']) ?></h5>

                            <!-- Placeholder updated by AJAX -->
                            <div id="availability-result-<?= $room['id'] ?>" class="availability-result mt-1 mb-1">
                                <?php if (!is_null($room['available_qty'])): ?>
                                    <?php if ($room['available_qty'] > 0): ?>
                                        <strong><span class="text-success"><i class="bi bi-calendar2-check"></i> <?= (int)$room['available_qty'] ?> room(s) available</span></strong>
                                    <?php else: ?>
                                        <strong><span class="text-danger"><i class="bi bi-calendar2-x"></i> Fully Booked</span></strong>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

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
                            <!-- Book Now links use current input values (client-side built when user clicks card or button) -->
                            <?php if ($room['available_qty'] > 0 || is_null($room['available_qty'])): ?>
                                <a style="background-color: #bd8f03ff; color: #fff;" href="room_details.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= (int)$no_of_rooms ?>&guests=<?= (int)$guests ?>&children=<?= (int)$children ?>"
                                   class="btn book-now-btn" data-room-id="<?= $room['id'] ?>">Book Now</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Not Available</button>
                            <?php endif; ?>
                        </div>
                    </div></a>
                </div>
                            
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- JS: place at end of page (or combine into your main.js) -->
<script>
  const savedChildren = sessionStorage.getItem('last_children');
if (savedChildren !== null) {
  const childInput = document.getElementById('num_children');
  if (childInput) childInput.value = savedChildren;
}

(function () {
  'use strict';
  // Helper
  const formatDate = d => new Date(d).toISOString().split('T')[0];
  // Elements
  const checkInEl = document.getElementById('check_in');
  const checkOutEl = document.getElementById('check_out');
  const form = document.getElementById('roomsAvailabilityForm');

  // Populate date inputs from URL if present, otherwise leave blank (prevents stale session values)
  const urlParams = new URLSearchParams(window.location.search);
  const urlCheckIn = urlParams.get('check_in') || '';
  const urlCheckOut = urlParams.get('check_out') || '';

  if (checkInEl) {
    if (urlCheckIn) {
      checkInEl.value = urlCheckIn;
      checkInEl.defaultValue = urlCheckIn;
    } else {
      checkInEl.value = '';
      checkInEl.defaultValue = '';
    }
  }
  if (checkOutEl) {
    if (urlCheckOut) {
      checkOutEl.value = urlCheckOut;
      checkOutEl.defaultValue = urlCheckOut;
    } else {
      checkOutEl.value = '';
      checkOutEl.defaultValue = '';
    }
  }

  // Set min for all date inputs to today
  try {
    const todayStr = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(i => {
      if (i) i.setAttribute('min', todayStr);
    });
  } catch (e) { console.warn(e); }

  // Auto-set checkout to next day when checkin changes
  if (checkInEl && checkOutEl) {
    checkInEl.addEventListener('change', function () {
      const ci = new Date(this.value);
      if (isNaN(ci)) return;
      const next = new Date(ci); next.setDate(next.getDate() + 1);
      if (!checkOutEl.value || new Date(checkOutEl.value) <= ci) {
        checkOutEl.value = formatDate(next);
      }
      checkOutEl.setAttribute('min', formatDate(next));
    });
  }

  // pageshow: clear fields when page is restored from bfcache or reloaded and URL doesn't include them.
  window.addEventListener('pageshow', function (e) {
    // detect reload or bfcache restore
    const navEntries = performance.getEntriesByType ? performance.getEntriesByType('navigation') : [];
    const navType = (navEntries && navEntries[0]) ? navEntries[0].type : (performance.navigation ? performance.navigation.type : '');
    const isReload = (navType === 'reload' || navType === 1);

    if (e.persisted || isReload) {
      // If the URL contains a param, keep it. Otherwise reset to sensible defaults.
      const fields = [
        {name:'check_in', el: checkInEl, default: ''},
        {name:'check_out', el: checkOutEl, default: ''},
        {name:'no_of_rooms', el: document.getElementById('no_of_rooms'), default: 1},
        {name:'guests', el: document.getElementById('guests'), default: 2},
        {name:'num_children', el: document.getElementById('num_children'), default: 0},
      ];
      fields.forEach(f=>{
        if (!f.el) return;
        if (urlParams.has(f.name)) {
          f.el.value = urlParams.get(f.name);
          f.el.defaultValue = urlParams.get(f.name);
        } else {
          f.el.value = f.default;
          f.el.defaultValue = f.default;
        }
      });
    }

    // Also: small delayed override in case browser restores values after pageshow
    setTimeout(() => {
      const paramsNow = new URLSearchParams(location.search);
      if (!paramsNow.get('check_in') && checkInEl) { checkInEl.value = ''; checkInEl.defaultValue = ''; }
      if (!paramsNow.get('check_out') && checkOutEl) { checkOutEl.value = ''; checkOutEl.defaultValue = ''; }
      if (!paramsNow.has('no_of_rooms')) {
        const el = document.getElementById('no_of_rooms');
        if (el) el.value = 1;
      }
      if (!paramsNow.has('guests')) {
        const el = document.getElementById('guests');
        if (el) el.value = 2;
      }
      if (!paramsNow.has('num_children')) {
        const el = document.getElementById('num_children');
        if (el) el.value = 0;
      }
    }, 120);
  });

  // Prevent browser autocomplete weirdness for number inputs
  document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function(){ this.value = this.value.replace(/^0+/, ''); });
    input.addEventListener('blur', function(){ if (this.value === '' ) this.value = 0; });
  });


  // Intercept Book Now clicks to use live form values
  document.querySelectorAll('.book-now-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      // Build URL w/ current fields
      const checkIn = document.getElementById('check_in').value || '';
      const checkOut = document.getElementById('check_out').value || '';
      const rooms = document.querySelector('input[name="no_of_rooms"]').value || '';
      const guests = document.querySelector('input[name="guests"]').value || '';
      const children = document.querySelector('input[name="num_children"]').value || '';

      const roomId = this.getAttribute('data-room-id') || this.dataset.roomId;
      const url = `room_details.php?room_id=${encodeURIComponent(roomId)}&check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}&no_of_rooms=${encodeURIComponent(rooms)}&guests=${encodeURIComponent(guests)}&children=${encodeURIComponent(children)}`;
      // Navigate
      window.location.href = url;
      e.preventDefault();
    });
  });

  // Room card click → navigate to detail page (uses current input values)
  document.querySelectorAll(".single_offers").forEach(card => {
      card.addEventListener("click", function(e) {
          if (e.target.closest(".btn")) return;
          const roomId = this.dataset.roomId;
          const checkIn = document.getElementById('check_in').value;
          const checkOut = document.getElementById('check_out').value;
          const rooms = document.querySelector('input[name="no_of_rooms"]').value;
          const guests = document.querySelector('input[name="guests"]').value;
          const children = document.querySelector('input[name="num_children"]').value;

          const url = `room_details.php?room_id=${encodeURIComponent(roomId)}&check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}&no_of_rooms=${encodeURIComponent(rooms)}&guests=${encodeURIComponent(guests)}&children=${encodeURIComponent(children)}`;
          window.location.href = url;
      });
  });
// console.log({
//     check_in: checkIn,
//     check_out: checkOut,
//     guests: guests,
//     no_of_rooms: roomsRequested
// });

  // AVAILABILITY AJAX: single top form updates all room placeholders and scrolls to result
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const checkInVal = this.querySelector('[name="check_in"]').value;
      const checkOutVal = this.querySelector('[name="check_out"]').value;
      const rooms = this.querySelector('[name="no_of_rooms"]').value || 0;
      const adults = this.querySelector('[name="guests"]').value || 1;
      const children = this.querySelector('[name="num_children"]').value || 0;

       // 🔑 Preserve child count after AJAX
  sessionStorage.setItem('last_children', childrenVal);

      // Basic validation
      if (!checkInVal || !checkOutVal) {
        alert('Please provide check-in and check-out dates.');
        return;
      }

      // Show checking message
      document.querySelectorAll('.availability-result').forEach(el => el.innerHTML = '<span class="text-muted">Checking...</span>');

      fetch('ajaxCheckAvailability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            check_in: checkInVal,
            check_out: checkOutVal,
            no_of_rooms: rooms,
            guests: adults,
            num_children: children
        })
      })
      .then(res => res.json())
      .then(data => {
        // Handle multiple shapes of response
        let roomsData = null;
        if (data === null) {
          throw new Error('Empty response');
        }
        if (data.success === true && Array.isArray(data.results)) {
          roomsData = data.results;
        } else if (data.status === 'success' && Array.isArray(data.rooms)) {
          roomsData = data.rooms;
        } else if (data.success === true && data.rooms && Array.isArray(data.rooms)) {
          roomsData = data.rooms;
        } else if (Array.isArray(data)) {
          roomsData = data;
        } else {
          const msg = data.message || data.error || 'No availability data returned';
          document.querySelectorAll('.availability-result').forEach(el => el.innerHTML = `<strong><span class="text-danger">${msg}</span></strong>`);
          // scroll to first result container so user sees message
          const first = document.querySelector('.availability-result');
          if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
          return;
        }

        // Update placeholders and Book Now buttons
        roomsData.forEach(r => {
          // r might have keys: room_id / id; available_qty or rooms_available
          const id = r.room_id ?? r.id ?? r.roomId ?? null;
          const available_qty = r.available_qty ?? r.rooms_available ?? r.roomsAvailable ?? r.rooms ?? null;
          if (!id) return;
          const placeholder = document.getElementById(`availability-result-${id}`);
          if (!placeholder) return;
          if (Number(available_qty) > 0) {
            placeholder.innerHTML = `<strong><span class="text-success"><i class="bi bi-calendar2-check"></i> ${Number(available_qty)} room(s) available</span></strong>`;
            // enable Book Now for that card
            const bookBtn = document.querySelector(`.book-now-btn[data-room-id="${id}"]`);
            if (bookBtn) {
              bookBtn.classList.remove('btn-secondary'); bookBtn.classList.add('btn-primary');
              bookBtn.disabled = false;
            }
          } else {
            placeholder.innerHTML = `<strong><span class="text-danger"><i class="bi bi-calendar2-x"></i> Fully Booked</span></strong>`;
            // disable Book Now
            const bookBtn = document.querySelector(`.book-now-btn[data-room-id="${id}"]`);
            if (bookBtn) {
              bookBtn.classList.remove('btn-primary'); bookBtn.classList.add('btn-secondary');
              bookBtn.disabled = true;
            }
          }
        });

        // Mark rooms not included in response as 'Not available' and disable their buttons
        document.querySelectorAll('.availability-result').forEach(el => {
          const id = el.id.replace('availability-result-', '');
          const found = roomsData.some(r => String(r.room_id ?? r.id ?? '') === String(id));
          if (!found) {
            el.innerHTML = `<strong><span class="text-danger"><i class="bi bi-calendar2-x"></i> Not available</span></strong>`;
            const bookBtn = document.querySelector(`.book-now-btn[data-room-id="${id}"]`);
            if (bookBtn) {
              bookBtn.classList.remove('btn-primary'); bookBtn.classList.add('btn-secondary');
              bookBtn.disabled = true;
            }
          }
        });

        // Scroll to the first updated placeholder so user can see the result
        if (roomsData.length > 0) {
          const firstId = roomsData[0].room_id ?? roomsData[0].id ?? null;
          if (firstId) {
            const scrollTarget = document.getElementById(`availability-result-${firstId}`);
            if (scrollTarget) scrollTarget.scrollIntoView({behavior:'smooth', block:'center'});
          } else {
            const first = document.querySelector('.availability-result');
            if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
          }
        } else {
          const first = document.querySelector('.availability-result');
          if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
        }

      })
      .catch(err => {
        console.error('Availability fetch error', err);
        document.querySelectorAll('.availability-result').forEach(el => el.innerHTML = `<strong><span class="text-danger">Error checking availability.</span></strong>`);
        const first = document.querySelector('.availability-result');
        if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
      });
    });
  } // end if form
})();
</script>