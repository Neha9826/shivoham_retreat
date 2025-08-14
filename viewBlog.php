<?php
include 'db.php';
$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM blogs WHERE slug = ? LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

if (!$blog) {
    header("Location: blog.php");
    exit;
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="bradcam_area breadcam_bg_1">
        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
    </div>

    <section class="blog_area single-post-area section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 posts-list">
                    <div class="single-post">
                        <div class="feature-img">
                            <img class="img-fluid" src="<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="">
                        </div>
                        <div class="blog_details">
                            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                            <ul class="blog-info-link mt-3 mb-4">
                                <li><i class="fa fa-user"></i> <?php echo htmlspecialchars($blog['author']); ?></li>
                                <li><i class="fa fa-tags"></i> <?php echo htmlspecialchars($blog['category']); ?></li>
                            </ul>
                            <?php echo $blog['content']; ?>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <?php include 'includes/blog_comments.php'; ?>
                </div>

                <!-- Sidebar -->
                <?php include 'includes/blog_sidebar.php'; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/form.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
