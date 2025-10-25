<?php
// nearbyPlaces.php
include 'db.php';
require_once __DIR__ . '/includes/helpers.php';

// Fetch all nearby places from the main table
$nearbyPlaces = $conn->query("SELECT * FROM nearby_places_main ORDER BY id DESC");

// Fetch a limited number of places for the fixed sidebar list
$sidebarPlaces = $conn->query("SELECT * FROM nearby_places_main ORDER BY id DESC LIMIT 4");

// Helper function to truncate a string and add a "Read More" link
function truncate_string_with_link($string, $length, $link) {
    if (strlen($string) > $length) {
        $string = substr($string, 0, $length) . '...';
        return $string . ' <a href="' . htmlspecialchars($link) . '">Read More</a>';
    }
    return $string;
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>
<style>
    .fixed-sidebar {
        position: -webkit-sticky; /* For Safari */
        position: sticky;
        top: 60px; /* Adjust this value to control the space from the top */
    }
    .scrollable-list {
        max-height: 400px; /* Adjust this height as needed */
        overflow-y: auto;
    }
    /* Simple styling for the list items to prevent overflow */
    .media-body h3 {
        word-wrap: break-word;
    }
</style>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/fixed_social_bar.php'; ?>
    
    <div class="bradcam_area breadcam_bg_1">
        <h3>Nearby Places</h3>
    </div>
    <section class="blog_area section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mb-5 mb-lg-0">
                    <div class="blog_left_sidebar">
                        <?php if ($nearbyPlaces->num_rows > 0): ?>
                            <?php while ($row = $nearbyPlaces->fetch_assoc()): ?>
                                <article class="blog_item">
                                    <div class="blog_item_img">
                                        <img class="card-img rounded-0"
                                             src="<?= htmlspecialchars(build_image_url($row['main_image'])); ?>"
                                             alt="<?= htmlspecialchars($row['title']); ?>">
                                             
                                    </div>
                                    <div class="blog_details">
                                        <a href="viewPlace.php?id=<?= $row['id']; ?>">
                                            <h2><?= htmlspecialchars($row['title']); ?></h2>
                                        </a>
                                        <p>
                                            <?= truncate_string_with_link(stripslashes($row['description']), 200, 'viewPlace.php?id=' . $row['id']); ?>
                                        </p>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center">
                                <h3>No nearby places found.</h3>
                                <p>Please check back later or contact us for more information.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div  class="col-lg-4">
                    <div class="blog_right_sidebar fixed-sidebar">
                        <?php if ($sidebarPlaces->num_rows > 0): ?>
                        <aside style="background-color: #f5f5f5;" class="single_sidebar_widget popular_post_widget">
                            <h3 class="widget_title">All Nearby Places</h3>
                            <div class="scrollable-list">
                                <?php while ($sideRow = $sidebarPlaces->fetch_assoc()): ?>
                                <div class="media post_item">
                                    <a href="<?= htmlspecialchars($sideRow['Maps_link']); ?>" target="_blank">
                                        <img src="<?= htmlspecialchars(build_image_url($sideRow['main_image'])); ?>" alt="post" style="width: 80px; height: 80px; object-fit: cover;">
                                    </a>
                                    <div class="media-body">
                                        <a href="<?= htmlspecialchars($sideRow['Maps_link']); ?>" target="_blank">
                                            <h3><?= htmlspecialchars($sideRow['title']); ?></h3>
                                        </a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </aside>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include 'includes/forQuery.php'; ?>
    <?php include 'includes/insta_area.php'; ?>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/form.php'; ?>
    <?php include 'includes/js.php'; ?>

</body>
</html>