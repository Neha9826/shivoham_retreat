<?php
session_start();
include 'db.php';

// Robust image path resolver
require_once __DIR__ . '/includes/helpers.php';

function blog_image_url(?string $dbPath): string {
    if (!$dbPath) {
        return base_url() . '/uploads/no-image.jpg';
    }

    $dbPath = trim($dbPath);

    // Already full URL
    if (preg_match('#^https?://#i', $dbPath)) {
        return $dbPath;
    }

    // Normalize slashes
    $dbPath = str_replace('\\', '/', $dbPath);

    // Ensure it always starts with /uploads/
    if (stripos($dbPath, 'uploads/') === 0 || stripos($dbPath, '/uploads/') === 0) {
        $dbPath = '/' . ltrim($dbPath, '/');
    } else {
        $dbPath = '/uploads/' . ltrim($dbPath, '/');
    }

    return base_url() . $dbPath;
}


$blogs = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
?>
<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/fixed_social_bar.php'; ?>
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
                                        <li>
                                            
                                            <!-- <div class="share-container mt-3"> -->
                                    <button class="btn  btn-sm share-blog-btn"
                                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                            data-url="/ShivohamRetreat/viewBlog.php?slug=<?php echo urlencode($row['slug']); ?>"
                                            data-image="<?php echo htmlspecialchars(blog_image_url($row['featured_image'])); ?>">
                                        <i class="fa fa-share-alt"></i> Share
                                    </button>
                                <!-- </div> -->
                                        </li>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Sharing logic start ---
            const shareButtons = document.querySelectorAll('.share-blog-btn');

            shareButtons.forEach(button => {
                const title = button.dataset.title || 'Check out this blog post!';
                const url = window.location.origin + '/' + button.dataset.url;
                const image = button.dataset.image;

                const shareData = {
                    title: title,
                    text: 'Check out this blog post: ' + title,
                    url: url,
                };

                if (navigator.share) {
                    button.addEventListener('click', async () => {
                        try {
                            await navigator.share(shareData);
                            console.log('Content shared successfully');
                        } catch (err) {
                            console.error('Error sharing:', err.message);
                        }
                    });
                } else {
                    button.addEventListener('click', () => {
                        const fallbackHtml = `
                            <div class="d-flex align-items-center">
                                <span class="d-inline-block me-2">Share:</span>
                                <a href="https://wa.me/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}" target="_blank" title="Share on WhatsApp" class="me-2"><i class="fa fa-whatsapp fa-2x"></i></a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.url)}" target="_blank" title="Share on Facebook" class="me-2"><i class="fa fa-facebook fa-2x"></i></a>
                                <a href="https://www.instagram.com/direct/inbox/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}" target="_blank" title="Share on Instagram" class="me-2"><i class="fa fa-instagram fa-2x"></i></a>
                            </div>
                        `;
                        const shareContainer = button.closest('.share-container');
                        if (shareContainer) {
                            shareContainer.innerHTML = fallbackHtml;
                        } else {
                            button.parentNode.innerHTML = fallbackHtml;
                        }
                    });
                }
            });
            // --- Sharing logic end ---
        });
    </script>
</body>
</html>