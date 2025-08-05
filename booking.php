<?php
include 'db.php';

// Use only 'room_id' as the URL parameter
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$roomDetails = null;

$checkIn  = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';


// Fetch the selected room's full details
if ($roomId > 0) {
    $roomResult = mysqli_query($conn, "SELECT * FROM rooms WHERE id = $roomId");
    $roomDetails = mysqli_fetch_assoc($roomResult);

    // Now also fetch images using same $roomId
    
    // $roomId = $room['id'];
    $imageSql = "SELECT image_path FROM room_images WHERE room_id = $roomId";
    $imageResult = $conn->query($imageSql);

    $imageRow = ($imageResult && $imageResult->num_rows > 0) ? $imageResult->fetch_assoc() : null;
    $imagePath = $imageRow['image_path'] ?? 'assets/img/default-room.jpg';

    // Check if image path already starts with 'uploads/', then prepend 'admin/' because you're outside admin folder
    if ($imagePath && str_starts_with($imagePath, 'uploads/')) {
        $imageSrc = 'admin/' . $imagePath;
    } else {
        $imageSrc = $imagePath;
    }
    
}

?>
<!doctype html>
<html class="no-js" lang="zxx">
    <?php include 'includes/head.php'; ?>

    <body>
    <?php include 'includes/header.php'; ?>

    <!-- bradcam_area_start -->
    <div class="bradcam_area breadcam_bg_1">
        <h3>Luxury Rooms</h3>
    </div>

    <!-- bradcam_area_end -->
    <!-- Room details section -->

        <!-- <?php if ($roomDetails): ?>
            <div class="container mt-4">
                <div class="room-info-bar">
                    <h4><?= htmlspecialchars($roomDetails['room_name']) ?></h4>

                    <div class="room-info-item">
                        <strong>Description:</strong>
                        <span><?= htmlspecialchars($roomDetails['description']) ?></span>
                    </div>

                    <div class="room-info-item">
                        <strong>Capacity:</strong>
                        <span><?= $roomDetails['room_capacity'] ?> persons</span>
                    </div>

                    <div class="room-info-item">
                        <strong>Price per Night:</strong>
                        <span>₹<?= $roomDetails['price_per_night'] ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?> -->

        <!-- Booking form -->
            <?php include 'bookingForm.php'; ?>
        <!-- Booking form -->

        <!-- forQuery_start -->
        <?php include 'includes/forQuery.php'; ?>
        <!-- forQuery_end-->

        <!-- instragram_area_start -->
        <?php include 'includes/insta_area.php'; ?>
        <!-- instragram_area_end -->
        
        <!-- footer -->
        <?php include 'includes/footer.php'; ?>

        <!-- form itself end-->
        <?php include 'includes/form.php'; ?>
        <!-- form itself end -->


        <!-- JS here -->
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

        <!--contact js-->
        <script src="js/contact.js"></script>
        <script src="js/jquery.ajaxchimp.min.js"></script>
        <script src="js/jquery.form.js"></script>
        <script src="js/jquery.validate.min.js"></script>
        <script src="js/mail-script.js"></script>

        <script src="js/main.js"></script>
        <script>
            $('#room_id, #check_in, #check_out, #guests, #children, #extra_beds, #extra_bed_age_group_id, #meal_plan_id').on('change', function() {
                $.ajax({
                    url: 'calculatePrice.php',
                    method: 'POST',
                    data: $('#bookingForm').serialize(),
                    success: function(response) {
                        $('#total_price').val(response);
                    }
                });
            });
        </script>

        <script>
function calculateTotal() {
    const formData = new FormData();

    formData.append("room_id", document.querySelector('[name="room_id"]').value);
    formData.append("no_of_rooms", document.querySelector('[name="no_of_rooms"]').value);
    formData.append("no_of_adults", document.querySelector('[name="no_of_adults"]').value);
    formData.append("no_of_children", document.querySelector('[name="no_of_children"]').value);
    formData.append("meal_plan_id", document.querySelector('[name="meal_plan_id"]').value);
    formData.append("check_in", document.querySelector('[name="check_in"]').value);
    formData.append("check_out", document.querySelector('[name="check_out"]').value);

    const extraBeds = document.querySelectorAll('[name="extra_beds[]"]');
    extraBeds.forEach(bed => {
        formData.append("extra_beds[]", bed.value);
    });

    fetch('calculatePrice.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        document.getElementById("total_price").value = "₹" + data;
    })
    .catch(error => {
        console.error("Error:", error);
    });
}
</script>


    </body>
    </html>