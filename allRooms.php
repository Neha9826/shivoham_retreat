<?php 
session_start(); 
$room_id = $_GET['room_id'] ?? '';
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$no_of_rooms = $_GET['no_of_rooms'] ?? 1;
$guests = $_GET['guests'] ?? 2;
$children = $_GET['children'] ?? 0;
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
     <!-- <div id="form-message" class="mt-3"></div> -->
    <?php include 'rooms.php'; ?>
    <!-- offers_area_end -->

        <!-- offers_area_end -->

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