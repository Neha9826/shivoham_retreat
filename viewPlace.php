<?php
// viewPlace.php
include 'db.php';
require_once __DIR__ . '/includes/helpers.php'; // Assuming this helper file exists.

// Get the place ID from the URL
$placeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($placeId === 0) {
    header("Location: nearbyPlaces.php");
    exit;
}

// Fetch the main nearby place data
$placeQuery = "SELECT * FROM nearby_places_main WHERE id = ?";
$placeStmt = $conn->prepare($placeQuery);
$placeStmt->bind_param("i", $placeId);
$placeStmt->execute();
$placeResult = $placeStmt->get_result();
$place = $placeResult->fetch_assoc();

if (!$place) {
    header("Location: nearbyPlaces.php");
    exit;
}

// Fetch the sections for this place
$sectionsQuery = "SELECT * FROM nearby_places_sections WHERE nearby_place_id = ? ORDER BY id ASC";
$sectionsStmt = $conn->prepare($sectionsQuery);
$sectionsStmt->bind_param("i", $placeId);
$sectionsStmt->execute();
$sectionsResult = $sectionsStmt->get_result();
?>

<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>
<style>
/* Make all owl-carousel items square */
/* Make slider slightly smaller */
.slider, .place-slider, .carousel {
    max-width: 90%;
    max-height: 400px;
    margin: 0 auto;
    overflow: hidden;
}

.slider img, .place-slider img, .carousel img {
    width: 100%;
    height: auto;
    object-fit: cover;
}

/* Ensure text starts at the very top of column */
.about_info {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    height: 100%;
}
</style>

<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/fixed_social_bar.php'; ?>

<div class="bradcam_area breadcam_bg_1" style="background-image: url('<?= htmlspecialchars(build_image_url($place['main_image'])); ?>');">
    <h3><?= htmlspecialchars($place['title']); ?></h3>
</div>

<div class="about_area mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h1><?= htmlspecialchars($place['title']); ?></h1>
                <p>
                    <?= stripslashes($place['description']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
$count = 0;
while ($section = $sectionsResult->fetch_assoc()):
    $count++;
    $is_text_left = ($count % 2 != 0);

    // Fetch images for the current section
    $imagesQuery = "SELECT image_path FROM nearby_places_images WHERE nearby_place_section_id = ?";
    $imagesStmt = $conn->prepare($imagesQuery);
    $imagesStmt->bind_param("i", $section['id']);
    $imagesStmt->execute();
    $imagesResult = $imagesStmt->get_result();
    $images = $imagesResult->fetch_all(MYSQLI_ASSOC);
    $has_images = count($images) > 0;
?>
<div class="about_area section-padding" style="padding-top:15px; padding-bottom:15px;">
    <div class="container" style="padding:0;">
        <div class="row align-items-start" style="margin:0;">
            <?php if ($is_text_left): ?>
                <!-- Text Left, Images Right -->
                <div class="col-xl-5 col-lg-5" style="padding:0 10px 0 0;">
                    <div class="about_info" style="margin:0; padding:0;">
                        <div class="section_title" style="margin-bottom:5px;">
                            <span><?= htmlspecialchars($section['side_heading']); ?></span>
                            <h3 style="margin:3px 0 2px 0; padding:0;"><?= htmlspecialchars($section['side_heading']); ?></h3>
                        </div>
                        <p style="margin:0; padding:0;"><?= stripslashes($section['description']); ?></p>
                    </div>
                </div>
                <div class="col-xl-7 col-lg-7" style="padding:0 0 0 10px;">
                    <?php if ($has_images): ?>
                        <div class="owl-carousel small-slider" style="margin:0;">
                            <?php foreach ($images as $image): ?>
                                <div class="slider" style="max-width:90%; max-height:350px; margin:0 auto; overflow:hidden;">
                                    <img src="<?= htmlspecialchars(build_image_url($image['image_path'])); ?>" 
                                         style="width:100%; height:100%; object-fit:cover;" 
                                         alt="Place Image">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Images Left, Text Right -->
                <div class="col-xl-7 col-lg-7" style="padding:0 10px 0 0;">
                    <?php if ($has_images): ?>
                        <div class="owl-carousel small-slider" style="margin:0;">
                            <?php foreach ($images as $image): ?>
                                <div class="slider" style="max-width:90%; max-height:350px; margin:0 auto; overflow:hidden;">
                                    <img src="<?= htmlspecialchars(build_image_url($image['image_path'])); ?>" 
                                         style="width:100%; height:100%; object-fit:cover;" 
                                         alt="Place Image">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-xl-5 col-lg-5" style="padding:0 0 0 10px;">
                    <div class="about_info" style="margin:0; padding:0;">
                        <div class="section_title" style="margin-bottom:5px;">
                            <span><?= htmlspecialchars($section['side_heading']); ?></span>
                            <h3 style="margin:3px 0 2px 0; padding:0;"><?= htmlspecialchars($section['side_heading']); ?></h3>
                        </div>
                        <p style="margin:0; padding:0;"><?= stripslashes($section['description']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php include 'includes/forQuery.php'; ?>
<?php include 'includes/insta_area.php'; ?>
<?php include 'includes/footer.php'; ?>
<?php include 'includes/form.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
$(document).ready(function(){
    $(".owl-carousel.small-slider").owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 3000,
        autoplayHoverPause: true,
        nav: true,
        dots: true
    });
});
</script>
</body>
</html>
