<?php
session_start();
include 'db.php'; 
?>
<?php include_once __DIR__ . '/config.php'; ?>

<!doctype html>
    <html class="no-js" lang="zxx">
    <?php include 'includes/head.php'; ?>
    <?php require_once __DIR__ . '/includes/helpers.php'; ?>

    <body>

        <!-- header-start -->
        <?php include 'includes/header.php'; ?>
        <!-- header-end -->

        <!-- fixed_social_bar-start -->
        <?php include 'includes/fixed_social_bar.php'; ?>
        <!-- fixed_social_bar-end -->

        <!-- slider_area_start -->
        <?php include 'includes/slider.php'; ?>
        <!-- slider_area_end -->

        <!-- offers_area_start -->
        <?php include 'rooms.php'; ?>
        <!-- offers_area_end -->

        <!-- about_area_start -->
        <?php include 'includes/about_1.php'; ?>
        <!-- about_area_end -->

        <!-- video_area_start -->
        <!-- video_area_end -->

        <!-- about_area_start -->
        <?php include 'includes/about_2.php'; ?>
        <!-- about_area_end -->

        <!-- forQuery_start -->
        <?php include 'includes/forQuery.php'; ?>
        <!-- forQuery_end-->

        <!-- instragram_area_start -->
        <?php include 'includes/insta_area.php'; ?>
        <!-- instragram_area_end -->

        <!-- footer -->
        <?php include 'includes/footer.php'; ?>

        <!-- link that opens popup -->

        <!-- form itself end-->
        <?php include 'includes/form.php'; ?>
        <!-- form itself end -->

        
        <!-- Bootstrap JS -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->

        <!-- JS here -->
            <!-- Core JS -->
    <script src="<?= BASE_URL ?>js/vendor/jquery-1.12.4.min.js"></script>
    <script src="<?= BASE_URL ?>js/vendor/modernizr-3.5.0.min.js"></script>
    <script src="<?= BASE_URL ?>js/popper.min.js"></script>
    <script src="<?= BASE_URL ?>js/bootstrap.min.js"></script>

    <!-- Owl Carousel -->
    <script src="<?= BASE_URL ?>js/owl.carousel.min.js"></script>

    <!-- Plugins -->
    <script src="<?= BASE_URL ?>js/isotope.pkgd.min.js"></script>
    <script src="<?= BASE_URL ?>js/ajax-form.js"></script>
    <script src="<?= BASE_URL ?>js/waypoints.min.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.counterup.min.js"></script>
    <script src="<?= BASE_URL ?>js/imagesloaded.pkgd.min.js"></script>
    <script src="<?= BASE_URL ?>js/scrollIt.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.scrollUp.min.js"></script>
    <script src="<?= BASE_URL ?>js/wow.min.js"></script>
    <script src="<?= BASE_URL ?>js/nice-select.min.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.slicknav.min.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.magnific-popup.min.js"></script>
    <script src="<?= BASE_URL ?>js/plugins.js"></script>
    <script src="<?= BASE_URL ?>js/gijgo.min.js"></script>

    <!-- Contact Form JS -->
    <script src="<?= BASE_URL ?>js/contact.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.ajaxchimp.min.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.form.js"></script>
    <script src="<?= BASE_URL ?>js/jquery.validate.min.js"></script>
    <script src="<?= BASE_URL ?>js/mail-script.js"></script>

    <!-- Main JS (AFTER jQuery & plugins) -->
    <script src="<?= BASE_URL ?>js/main.js"></script>

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