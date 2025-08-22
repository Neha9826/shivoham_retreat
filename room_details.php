<?php
// room_details.php (Goibibo-style Room Selection Page with Advanced Search)
session_start();
include 'db.php';

// 📌 YOU MUST UPDATE THIS PATH WITH YOUR SUBFOLDER NAME
$basePath = ''; // For example: '/my-hotel-project/' or '/hotel/'

// Get parameters from URL, with session as fallback
$room_id     = $_GET['room_id'] ?? null;
$check_in    = $_GET['check_in'] ?? $_SESSION['check_in'] ?? date('Y-m-d');
$check_out   = $_GET['check_out'] ?? $_SESSION['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$no_of_rooms = $_GET['no_of_rooms'] ?? $_SESSION['no_of_rooms'] ?? 1;
$guests      = $_GET['guests'] ?? $_SESSION['guests'] ?? 2;
$children    = $_GET['children'] ?? $_SESSION['num_children'] ?? 0;

// Save to session for consistency across pages
$_SESSION['check_in']     = $check_in;
$_SESSION['check_out']    = $check_out;
$_SESSION['no_of_rooms']  = $no_of_rooms;
$_SESSION['guests']       = $guests;
$_SESSION['num_children'] = $children;

// Helper function to get all room data with pricing and availability
function get_all_room_data($conn, $check_in, $check_out, $guests, $children, $preferred_room_id = null) {
    global $basePath;
    $rooms_data = [];
    
    // Corrected SQL query from previous iteration.
    $sql = "SELECT r.*,
                   (SELECT GROUP_CONCAT(image_path) FROM room_images WHERE room_id = r.id) AS image_paths,
                   (SELECT GROUP_CONCAT(a.name, '|', a.icon_class)
                      FROM amenities a
                      JOIN room_amenities ra ON ra.amenity_id = a.id
                     WHERE ra.room_id = r.id) AS amenity_data
            FROM rooms r
            WHERE (r.base_adults + r.max_extra_with_bed + r.max_child_without_bed_5_12) >= ?
            ORDER BY FIELD(r.id, ?) DESC, r.id DESC";
    
    $stmt = $conn->prepare($sql);
    $total_guests_for_search = $guests + $children;
    $stmt->bind_param("ii", $total_guests_for_search, $preferred_room_id);
    $stmt->execute();
    $roomResult = $stmt->get_result();
    
    if ($roomResult && $roomResult->num_rows > 0) {
        while ($room = $roomResult->fetch_assoc()) {
            $room_id = (int)$room['id'];

            // 1. Get availability
            $total_qty = (int)$room['total_rooms'];
            if ($check_in && $check_out) {
                $conflictSql = "
                    SELECT COUNT(*) AS booked_count
                    FROM booking_rooms br
                    JOIN bookings b ON br.booking_id = b.id
                    WHERE br.room_id = $room_id
                      AND (b.check_in < '$check_out' AND b.check_out > '$check_in')
                ";
                $conflictResult = $conn->query($conflictSql);
                $booked = $conflictResult ? (int)$conflictResult->fetch_assoc()['booked_count'] : 0;
                $available = $total_qty - $booked;
                $room['available_qty'] = max(0, $available);
            } else {
                $room['available_qty'] = null;
            }

            // 2. Get seasonal prices for the check-in date
            $dayOfWeek = date('l', strtotime($check_in));
            $priceColumns = [
                'standard' => strtolower($dayOfWeek) . '_standard',
                'breakfast' => strtolower($dayOfWeek) . '_breakfast',
                'breakfast_lunch' => strtolower($dayOfWeek) . '_breakfast_lunch',
                'all_meals' => strtolower($dayOfWeek) . '_all_meals'
            ];
            $sql_prices = "SELECT " . implode(', ', $priceColumns) . " 
                           FROM room_seasonal_prices
                           WHERE room_id = ? AND ? BETWEEN start_date AND end_date
                           LIMIT 1";
            $stmt_prices = $conn->prepare($sql_prices);
            $stmt_prices->bind_param("is", $room_id, $check_in);
            $stmt_prices->execute();
            $seasonal_prices = $stmt_prices->get_result()->fetch_assoc();

            // 3. Prepare final prices for display, with fallback
            $room['meal_prices'] = [
                'standard' => $seasonal_prices[$priceColumns['standard']] ?? $room['standard_price'],
                'breakfast' => $seasonal_prices[$priceColumns['breakfast']] ?? $room['price_with_breakfast'],
                'breakfast_lunch' => $seasonal_prices[$priceColumns['breakfast_lunch']] ?? $room['price_with_breakfast_lunch'],
                'all_meals' => $seasonal_prices[$priceColumns['all_meals']] ?? $room['price_with_all_meals']
            ];

            // 4. Process images and amenities
            $images = [];
            if (!empty($room['image_paths'])) {
                $images = array_map(function($path) use ($basePath) {
                    if (strpos($path, 'admin/') !== 0 && strpos($path, 'assets/') !== 0) {
                        return $basePath . 'admin/' . $path;
                    }
                    return $basePath . $path;
                }, explode(',', $room['image_paths']));
            }
            $room['images'] = $images;

            $amenityList = [];
            if (!empty($room['amenity_data'])) {
                $pairs = explode(',', $room['amenity_data']);
                foreach ($pairs as $pair) {
                    [$name, $icon] = explode('|', $pair);
                    $amenityList[] = ['name' => $name, 'icon' => $icon ?: 'bi-check-circle'];
                }
            }
            $room['amenities'] = $amenityList;
            
            $rooms_data[] = $room;
        }
    }
    return $rooms_data;
}

$all_rooms = get_all_room_data($conn, $check_in, $check_out, $guests, $children, $room_id);

$meal_plan_names = [
    'standard' => 'Room Only',
    'breakfast' => 'Room with Breakfast',
    'breakfast_lunch' => 'Room with Breakfast & Lunch',
    'all_meals' => 'All Meals'
];
$meal_plan_features = [
    'standard' => ['No meals included', 'Free cancellation (check policy)'],
    'breakfast' => ['Complimentary Breakfast', 'Free cancellation (check policy)'],
    'breakfast_lunch' => ['Complimentary Breakfast & Lunch', 'Free cancellation (check policy)'],
    'all_meals' => ['All Meals included (Breakfast, Lunch & Dinner)', 'Free cancellation (check policy)']
];
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
      .room-section {
          border: 1px solid #e1e1e1;
          border-radius: 8px;
          margin-bottom: 30px;
          overflow: hidden;
      }
      .room-header {
          padding: 20px;
          display: flex;
          align-items: flex-start;
      }
      .room-image {
          width: 250px;
          height: 180px;
          object-fit: cover;
          border-radius: 8px;
          flex-shrink: 0;
          cursor: pointer;
      }
      .room-info {
          padding-left: 20px;
          flex-grow: 1;
      }
      .meal-plan-item {
          border-top: 1px solid #e1e1e1;
          padding: 15px 20px;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      .capacity-details {
        font-size: 0.9em;
        line-height: 1.2;
        margin-bottom: 10px;
      }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- fixed_social_bar-start -->
        <?php include 'includes/fixed_social_bar.php'; ?>
        <!-- fixed_social_bar-end -->

<div class="bradcam_area breadcam_bg_1">
    <h3>Available Rooms</h3>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-12">
            <form method="GET" action="room_details.php" class="card p-4 mb-5 shadow-sm">
                <h4 class="mb-3">Check Availability</h4>
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
                               value="<?= htmlspecialchars($no_of_rooms) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>No. of Adults:</label>
                        <input type="number" name="guests" min="1"
                               value="<?= htmlspecialchars($guests) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label>No. of Children:</label>
                        <input type="number" name="children" min="0"
                               value="<?= htmlspecialchars($children) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary mt-2 w-100">Update</button>
                    </div>
                </div>
            </form>
            
            <?php if (empty($all_rooms)): ?>
                <div class="alert alert-warning text-center">
                    No rooms found for the selected dates and guest count.
                </div>
            <?php else: ?>
                <?php foreach ($all_rooms as $room): ?>
                    <div class="room-section mb-5 shadow-sm">
                        <div class="room-header">
                            <img src="<?= htmlspecialchars($room['images'][0] ?? 'assets/img/default-room.jpg') ?>" 
                                 class="room-image" 
                                 alt="<?= htmlspecialchars($room['room_name']) ?>"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#roomDetailsModal"
                                 data-room-id="<?= $room['id'] ?>">
                            <div class="room-info">
                                <h3><?= htmlspecialchars($room['room_name']) ?></h3>
                                <?php if ($room['available_qty'] !== null): ?>
                                    <p class="mb-1">
                                        <?php if ($room['available_qty'] > 0): ?>
                                            <span class="text-success fw-bold"><?= $room['available_qty'] ?> room(s) available</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">Sold Out</span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <p><strong>Total Room Capacity:</strong><br><?= $room['room_capacity'] ?> persons</p>
                                <ul>
                                    <li>Base Adults: <?= $room['base_adults'] ?></li>
                                    <li>Max Adult/Child with Extra Bed: <?= $room['max_extra_with_bed'] ?> (₹<?= number_format($room['price_with_extra_bed'], 2) ?>)</li>
                                    <li>Child (5–12) without Bed: <?= $room['max_child_without_bed_5_12'] ?> (₹<?= number_format($room['price_child_5_12'], 2) ?>)</li>
                                    <li>Child (&lt;5) without Bed: <?= $room['max_child_without_bed_below_5'] ?>
                                        <?php if ($room['price_child_below_5'] > 0): ?>
                                            (₹<?= number_format($room['price_child_below_5'], 2) ?>)
                                        <?php else: ?>
                                            (Complimentary)
                                        <?php endif; ?>
                                    </li>
                                </ul>
                                <p><?= nl2br(htmlspecialchars(substr($room['description'] ?? '', 0, 150) . '...')) ?></p>
                                <div class="d-flex flex-wrap mb-2">
                                    <?php foreach ($room['amenities'] as $am): ?>
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            <i class="bi <?= htmlspecialchars($am['icon']) ?> me-1"></i>
                                            <?= htmlspecialchars($am['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <?php foreach ($room['meal_prices'] as $key => $price): ?>
                                <?php if ($price > 0): ?>
                                    <div class="meal-plan-item">
                                        <div class="me-auto">
                                            <h5><?= htmlspecialchars($meal_plan_names[$key]) ?></h5>
                                        </div>
                                        <div class="text-end d-flex align-items-center">
                                            <p class="lead fw-bold mb-0 me-4">₹<?= number_format($price, 2) ?></p>
                                            <a href="booking.php?room_id=<?= $room['id'] ?>&check_in=<?= urlencode($check_in) ?>&check_out=<?= urlencode($check_out) ?>&no_of_rooms=<?= (int)$no_of_rooms ?>&guests=<?= (int)$guests ?>&children=<?= (int)$children ?>&meal_plan=<?= $key ?>"
                                               class="btn btn-primary">Select</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/forQuery.php'; ?>
<?php include 'includes/insta_area.php'; ?>
<?php include 'includes/footer.php'; ?>
<?php include 'includes/form.php'; ?>

<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-labelledby="roomDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRoomName"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner" id="modalCarouselInner">
            <div class="text-center p-5 text-muted">Loading images...</div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
        <div class="mt-4">
          <h5 class="mb-2">Description</h5>
          <p id="modalRoomDescription" class="text-muted"></p>
          <h5 class="mt-4 mb-2">Amenities</h5>
          <div id="modalAmenities" class="d-flex flex-wrap">
            </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="js/vendor/modernizr-3.5.0.min.js"></script>
<script src="js/vendor/jquery-1.12.4.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/isotope.pkgd.min.js"></script>
<script src="js/ajax-form.js"></script>
<script src="js/waypoints.min.js"></script>
<script src="js/jquery.counterup.min.js"></script>
<script src="js/imagesloaded.pkgd.min.js"></script>
<script src="js/scrollIt.js"></script>
<script src="js/jquery.scrollUp.min.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/nice-select.min.js"></script>
<script src="js/jquery.slicknav.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/plugins.js"></script>
<script src="js/gijgo.min.js"></script>

<script src="js/contact.js"></script>
<script src="js/jquery.ajaxchimp.min.js"></script>
<script src="js/jquery.form.js"></script>
<script src="js/jquery.validate.min.js"></script>
<script src="js/mail-script.js"></script>
<script src="js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<script>
// Date helpers for the form
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


// MODAL LOGIC to dynamically load content on click
const roomDetailsModal = document.getElementById('roomDetailsModal');
const modalCarouselInner = document.getElementById('modalCarouselInner');
const modalRoomName = document.getElementById('modalRoomName');
const modalRoomDescription = document.getElementById('modalRoomDescription');
const modalAmenities = document.getElementById('modalAmenities');

roomDetailsModal.addEventListener('show.bs.modal', function (event) {
    const imageElement = event.relatedTarget;
    const roomId = imageElement.getAttribute('data-room-id');

    // Reset modal content
    modalRoomName.innerText = 'Loading...';
    modalRoomDescription.innerText = '';
    modalAmenities.innerHTML = '';
    modalCarouselInner.innerHTML = `<div class="text-center p-5 text-muted">Loading images...</div>`;

    fetch(`getRoomDetailsForModal.php?room_id=${roomId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                // If PHP script returns a specific error
                modalRoomName.innerText = 'Error';
                modalRoomDescription.innerText = data.error;
                modalCarouselInner.innerHTML = `<div class="text-center p-5 text-danger">${data.error}</div>`;
                return;
            }

            // Populate room details
            modalRoomName.innerText = data.room_name;
            modalRoomDescription.innerText = data.description;
            
            // Populate amenities
            modalAmenities.innerHTML = data.amenities.map(am => `
                <span class="badge bg-light text-dark border me-2 mb-2 p-2">
                    <i class="bi ${am.icon} me-1"></i>
                    ${am.name}
                </span>
            `).join('');

            // Populate carousel images
            const carouselItems = data.images.map((img, index) => `
                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                    <img src=\"${img}\" class="d-block w-100" style="height: 400px; object-fit: cover;">
                </div>
            `).join('');
            modalCarouselInner.innerHTML = carouselItems || `<div class="text-center p-5 text-muted">No images available.</div>`;

        })
        .catch(error => {
            console.error('Fetch error:', error);
            modalRoomName.innerText = 'Error';
            modalRoomDescription.innerText = 'An unknown error occurred. Please check the console for details.';
            modalAmenities.innerHTML = '';
            modalCarouselInner.innerHTML = `<div class="text-center p-5 text-danger">An unknown error occurred.</div>`;
        });
});
</script>
</body>
</html>