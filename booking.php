<?php
// booking.php
session_start();
include 'db.php';

// 📌 YOU MUST UPDATE THIS PATH WITH YOUR SUBFOLDER NAME
$basePath = ''; // For example: '/my-hotel-project/' or '/hotel/'

// Get parameters from URL, with session as fallback
$roomId      = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$checkIn     = $_GET['check_in'] ?? $_SESSION['check_in'] ?? date('Y-m-d');
$checkOut    = $_GET['check_out'] ?? $_SESSION['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$noOfRooms   = $_GET['no_of_rooms'] ?? $_SESSION['no_of_rooms'] ?? 1;
$guests      = $_GET['guests'] ?? $_SESSION['guests'] ?? 2;
$children    = $_GET['children'] ?? $_SESSION['num_children'] ?? 0;
$mealPlanKey = $_GET['meal_plan'] ?? 'standard';

// Calculate number of nights
$checkInDate  = new DateTime($checkIn);
$checkOutDate = new DateTime($checkOut);
$numNights = $checkInDate->diff($checkOutDate)->days;
$numNights = $numNights > 0 ? $numNights : 1;

$bookingData = null;

if ($roomId > 0) {
    // Fetch room details
    $roomSql = "SELECT * FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($roomSql);
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $roomDetails = $stmt->get_result()->fetch_assoc();

    if ($roomDetails) {
        // Fetch room images
        $imageSql = "SELECT image_path FROM room_images WHERE room_id = ?";
        $stmt = $conn->prepare($imageSql);
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $imageResult = $stmt->get_result();
        $imagePaths = [];
        while ($row = $imageResult->fetch_assoc()) {
            $path = $row['image_path'];
            if (strpos($path, 'admin/') !== 0 && strpos($path, 'assets/') !== 0) {
                $path = 'admin/' . $path;
            }
            $imagePaths[] = $basePath . $path;
        }
        $roomDetails['images'] = $imagePaths;
    }

    $bookingData = [
        'roomDetails' => $roomDetails,
        'checkIn'     => $checkIn,
        'checkOut'    => $checkOut,
        'numNights'   => $numNights,
        'noOfRooms'   => $noOfRooms,
        'guests'      => $guests,
        'children'    => $children,
        'mealPlan'    => $mealPlanKey,
    ];
}

$meal_plan_names = [
    'standard'          => 'Room Only',
    'breakfast'         => 'Room with Breakfast',
    'breakfast_lunch'   => 'Room with Breakfast & Lunch',
    'all_meals'         => 'All Meals'
];
?>
<!doctype html>
<html class="no-js" lang="zxx">
    <?php include 'includes/head.php'; ?>
    <style>
     .booking-page-content {
         padding-top: 150px;
     }
    </style>
    <body>
    <?php include 'includes/header.php'; ?>
    <!-- fixed_social_bar-start -->
        <?php include 'includes/fixed_social_bar.php'; ?>
        <!-- fixed_social_bar-end -->

    <div class="bradcam_area breadcam_bg_1">
        <h3>Booking Details</h3>
    </div>
    
    <?php if ($bookingData && $bookingData['roomDetails']): ?>
    <div class="booking-page-content container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="card p-4 shadow-sm mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= htmlspecialchars($bookingData['roomDetails']['images'][0] ?? 'assets/img/default-room.jpg') ?>" 
                             class="rounded me-3" style="width: 150px; height: 100px; object-fit: cover;"
                             alt="<?= htmlspecialchars($bookingData['roomDetails']['room_name']) ?>">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($bookingData['roomDetails']['room_name']) ?></h4>
                            <p class="text-muted mb-0"><?= htmlspecialchars($meal_plan_names[$mealPlanKey]) ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="mb-2 text-primary">Capacity Details</h6>
                        <p class="mb-1"><strong>Total Room Capacity:</strong> <?= htmlspecialchars($bookingData['roomDetails']['room_capacity']) ?> persons</p>
                        <ul class="list-unstyled mb-0 ms-3">
                            <li><i class="fa fa-person me-2"></i> Base Adults: <?= htmlspecialchars($bookingData['roomDetails']['base_adults']) ?></li>
                            <li><i class="fa fa-bed me-2"></i> Max Extra Bed: <?= htmlspecialchars($bookingData['roomDetails']['max_extra_with_bed']) ?> (₹<?= number_format(htmlspecialchars($bookingData['roomDetails']['price_with_extra_bed']), 2) ?>)</li>
                            <li><i class="fa fa-child-reaching me-2"></i> Child (5-12) without Bed: <?= htmlspecialchars($bookingData['roomDetails']['max_child_without_bed_5_12']) ?> (₹<?= number_format(htmlspecialchars($bookingData['roomDetails']['price_child_5_12']), 2) ?>)</li>
                            <li><i class="fa fa-child me-2"></i> Child (<5) without Bed: <?= htmlspecialchars($bookingData['roomDetails']['max_child_without_bed_below_5']) ?>
                                <?php if ($bookingData['roomDetails']['price_child_below_5'] > 0): ?>
                                    (₹<?= number_format(htmlspecialchars($bookingData['roomDetails']['price_child_below_5']), 2) ?>)
                                <?php else: ?>
                                    (Complimentary)
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row text-center border-top pt-3">
                        <div class="col-4">
                            <strong>Check-in</strong>
                            <p class="mb-0"><?= date('D, M j, Y', strtotime($checkIn)) ?></p>
                            <p class="text-muted mb-0">12:00 PM</p>
                        </div>
                        <div class="col-4">
                            <strong>Check-out</strong>
                            <p class="mb-0"><?= date('D, M j, Y', strtotime($checkOut)) ?></p>
                            <p class="text-muted mb-0">11:00 AM</p>
                        </div>
                        <div class="col-4">
                            <strong>Guests</strong>
                            <p class="mb-0"><span id="guestCount"><?= $guests ?></span> Adults, <span id="childrenCount"><?= $children ?></span> Children</p>
                            <p class="text-muted mb-0"><?= $numNights ?> Night<?= $numNights > 1 ? 's' : '' ?></p>
                        </div>
                    </div>
                </div>

                <div class="card p-4 shadow-sm mb-4">
                    <form id="bookingForm" method="POST" action="submitBooking.php">
                        <input type="hidden" name="room_id" value="<?= $roomDetails['id'] ?>">
                        <input type="hidden" name="meal_plan" value="<?= htmlspecialchars($bookingData['mealPlan']) ?>">
                
                        <h5 class="mb-3">Booking Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Check-in</label>
                                <input type="date" name="check_in" id="check_in" class="form-control" value="<?= htmlspecialchars($bookingData['checkIn']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check-out</label>
                                <input type="date" name="check_out" id="check_out" class="form-control" value="<?= htmlspecialchars($bookingData['checkOut']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rooms</label>
                                <input type="number" name="no_of_rooms" id="no_of_rooms" class="form-control" min="1" max="10" value="<?= htmlspecialchars($bookingData['noOfRooms']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Adults</label>
                                <input type="number" name="guests" id="guests" class="form-control" min="1" max="20" value="<?= htmlspecialchars($bookingData['guests']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Children</label>
                                <input type="number" name="children" id="children" class="form-control" min="0" max="10" value="<?= htmlspecialchars($bookingData['children']) ?>" required>
                            </div>
                        </div>
                        
                        <div id="extraBedInfo" class="mt-3"></div>
                
                        <div class="row g-3 mt-1" id="dynamicChildFields">
                        </div>
                
                        <h5 class="mb-3 mt-5">Contact Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Complete Booking</button>
                        </div>
                    </form>
                </div>
                </div>

            <div class="col-lg-4">
                <div class="card p-4 shadow-sm sticky-top" style="top: 150px;">
                    <h5 class="mb-3">Price Summary</h5>
                    <div id="price-summary-container">
                        <p class="text-center text-muted">Calculating price...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="booking-page-content container my-5 text-center">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Room not found!</h4>
            <p>The selected room is not available or the link is invalid. Please go back to the <a href="room_details.php" class="alert-link">room selection page</a> to book your stay.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/forQuery.php'; ?>
    <?php include 'includes/insta_area.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <?php include 'includes/form.php'; ?>
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
<script>
function updatePriceSummary() {
    const formData = {
        room_id: <?= $roomId ?>,
        check_in: $('#check_in').val(),
        check_out: $('#check_out').val(),
        no_of_rooms: $('#no_of_rooms').val(),
        guests: $('#guests').val(),
        children: $('#children').val(),
        meal_plan: "<?= htmlspecialchars($mealPlanKey) ?>",
        child_ages: []
    };
    
    // Collect child ages
    $('select[name="child_ages[]"]').each(function() {
        formData.child_ages.push($(this).val());
    });

    $.ajax({
        url: 'calculateBookingPrice.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            $('#price-summary-container').html(response);
        },
        error: function() {
            $('#price-summary-container').html('<p class="text-danger">Error calculating price.</p>');
        }
    });
}

function updateGuestFields() {
    const guests = parseInt($('#guests').val());
    const children = parseInt($('#children').val());
    const roomCapacity = <?= $bookingData['roomDetails']['room_capacity'] ?? 0 ?>;
    const maxExtraWithBed = <?= $bookingData['roomDetails']['max_extra_with_bed'] ?? 0 ?>;
    const baseAdults = <?= $bookingData['roomDetails']['base_adults'] ?? 0 ?>;
    const noOfRooms = parseInt($('#no_of_rooms').val());
    
    // Calculate potential extra beds needed based on total adults exceeding base adults across rooms
    let totalAdultsCapacity = baseAdults * noOfRooms;
    let extraAdultsNeeded = Math.max(0, guests - totalAdultsCapacity);

    // Update live summary
    $('#guestCount').text(guests);
    $('#childrenCount').text(children);

    // Dynamic Child Age Fields
    const childFieldsContainer = $('#dynamicChildFields');
    childFieldsContainer.empty();
    if (children > 0) {
        for (let i = 1; i <= children; i++) {
            childFieldsContainer.append(`
                <div class="col-md-6 mb-2">
                    <label>Child ${i} Age</label>
                    <select name="child_ages[]" class="form-control">
                        <option value="0">Below 5 years</option>
                        <option value="1">5-12 years</option>
                    </select>
                </div>
            `);
        }
    }

    // Dynamic Extra Bed Info (if adults exceed base capacity)
    const extraBedInfoContainer = $('#extraBedInfo');
    extraBedInfoContainer.empty();
    if (extraAdultsNeeded > 0) {
        // If extra adults are needed, check if they exceed the maximum extra bed capacity
        if (extraAdultsNeeded > (maxExtraWithBed * noOfRooms)) {
            extraBedInfoContainer.html(`
                <div class="alert alert-danger py-2">
                    <i class="fa fa-exclamation-triangle"></i> The number of extra beds required for adults (${extraAdultsNeeded}) exceeds the room's total extra bed capacity (${maxExtraWithBed * noOfRooms}). Please reduce the number of adults or rooms.
                </div>
            `);
        } else {
            extraBedInfoContainer.html(`
                <div class="alert alert-info py-2">
                    <i class="fa fa-info-circle"></i> An extra bed is required for ${extraAdultsNeeded} adult${extraAdultsNeeded > 1 ? 's' : ''}. Charges will be applied.
                </div>
            `);
        }
    }
}


$(document).ready(function() {
    // Initial setup and price calculation on page load
    updateGuestFields();
    updatePriceSummary();

    // Event listeners for form fields
    $('#no_of_rooms, #guests, #children, #check_in, #check_out').on('change', function() {
        updateGuestFields();
        updatePriceSummary();
    });
    
    $('#dynamicChildFields').on('change', 'select', function() {
            updatePriceSummary();
    });

    // Handle form submission with AJAX
    $('#bookingForm').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        // Get the form data
        const formData = $(this).serialize();

        // Send the data via AJAX
        $.ajax({
            url: 'submitBooking.php',
            method: 'POST',
            data: formData,
            dataType: 'json', // Expect a JSON response
            success: function(response) {
                if (response.success) {
                    // Redirect on successful booking
                    window.location.href = response.redirect_url;
                } else {
                    // Show error message if booking failed
                    // Replace alert with a more user-friendly modal or inline message
                    alert('Booking failed: ' + response.message); 
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle AJAX errors
                // Replace alert with a more user-friendly modal or inline message
                alert('An error occurred while submitting the booking. Please try again.');
                console.log(jqXHR, textStatus, errorThrown);
            }
        });
    });
});
</script>
    </body>
</html>