<?php
include 'db.php';

// Robust image path resolver
include 'includes/helpers.php';


$blogs = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
?>
<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="bradcam_area breadcam_bg_1">
        <h3>Our Blogs</h3>
    </div>

    <section class="blog_area section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mb-5 mb-lg-0">
                    <div class="blog_left_sidebar">
                        <?php while ($row = $blogs->fetch_assoc()): ?>
                            <article class="blog_item">
                                <div class="blog_item_img">
                                    <img class="card-img rounded-0" 
                                         src="<?php echo blog_image_url($row['featured_image']); ?>" 
                                         alt="">
                                    <a href="#" class="blog_item_date">
                                        <h3><?php echo date('d', strtotime($row['created_at'])); ?></h3>
                                        <p><?php echo date('M', strtotime($row['created_at'])); ?></p>
                                    </a>
                                </div>
                                <div class="blog_details">
                                    <a class="d-inline-block" href="viewBlog.php?slug=<?php echo urlencode($row['slug']); ?>">
                                        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                                    </a>
                                    <p><?php echo htmlspecialchars($row['excerpt']); ?></p>
                                    <ul class="blog-info-link">
                                        <li><i class="fa fa-user"></i> <?php echo htmlspecialchars($row['author']); ?></li>
                                        <li><i class="fa fa-tags"></i> <?php echo htmlspecialchars($row['category']); ?></li>
                                    </ul>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                </div>

                <?php include 'includes/blog_sidebar.php'; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/form.php'; ?>
    <?php include 'includes/js.php'; ?>
</body>
</html>
