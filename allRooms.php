    <?php
    include 'db.php';
    session_start();

    // Fetch check-in/out dates from session or default to today
    $checkin_date = $_SESSION['check_in'] ?? date('Y-m-d');
    $checkout_date = $_SESSION['check_out'] ?? date('Y-m-d');

    // SQL to fetch available rooms
    $sql = "SELECT * FROM rooms WHERE id NOT IN (
        SELECT room_id FROM bookings 
        WHERE (check_in < '$checkout_date' AND check_out > '$checkin_date')
    ) LIMIT 3";
    $result = $conn->query($sql);

    // Prepare available rooms
    $availability = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $availability[] = $row;
        }
    }

    // Create fake ResultSet to mimic mysqli result
    class ResultSet {
        private $data;
        function __construct($data) { $this->data = $data; }
        function fetch_assoc() { return array_shift($this->data); }
        function __get($name) {
            if ($name == 'num_rows') return count($this->data);
            return null;
        }
    }

    $checkin = $checkin_date;
    $checkout = $checkout_date;
    $result = new ResultSet($availability);
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

    <!-- offers_area_start -->
    <?php include 'rooms.php'; ?>
    <!-- offers_area_end -->

        <!-- offers_area_end -->

        <!-- features_room_start -->
        <?php include 'includes/features_room.php'; ?>
        <!-- features_room_end -->

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
            $('#datepicker').datepicker({
                iconsLibrary: 'fontawesome',
                icons: {
                rightIcon: '<span class="fa fa-caret-down"></span>'
            }
            });
            $('#datepicker2').datepicker({
                iconsLibrary: 'fontawesome',
                icons: {
                rightIcon: '<span class="fa fa-caret-down"></span>'
            }

            });
        </script>

    </body>
    </html>