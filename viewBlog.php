<?php
include 'db.php';

// Robust image path resolver
function blog_image_url(?string $dbPath, string $siteBase = ''): string {
    $placeholder = rtrim($siteBase, '/').'/uploads/no-image.jpg';

    if (!$dbPath) return $placeholder;

    if (preg_match('#^https?://#i', $dbPath)) return $dbPath;

    $p = str_replace('\\', '/', $dbPath);
    while (strpos($p, '../') === 0) $p = substr($p, 3);
    $p = ltrim($p, '/');

    $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $fsBase = $doc . rtrim($siteBase, '/');

    $relCandidates = [];
    if (strpos($p, 'admin/') === 0) {
        $relCandidates[] = $p;
        $relCandidates[] = substr($p, 6);
    } else {
        $relCandidates[] = $p;
        $relCandidates[] = 'admin/' . $p;
    }

    foreach ($relCandidates as $rel) {
        $fs = $fsBase . '/' . $rel;
        if (file_exists($fs)) {
            return rtrim($siteBase, '/') . '/' . $rel;
        }
    }

    return rtrim($siteBase, '/') . '/' . $p;
}

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

    <!-- fixed_social_bar-start -->
        <?php include 'includes/fixed_social_bar.php'; ?>
        <!-- fixed_social_bar-end -->

    <div class="bradcam_area breadcam_bg_1">
        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
    </div>

    <section class="blog_area single-post-area section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 posts-list">
                    <div class="single-post">
                        <div class="feature-img">
                            <img class="img-fluid" src="<?php echo blog_image_url($blog['featured_image']); ?>" alt="">
                        </div>
                        <div class="blog_details">
                            <h2><?php echo htmlspecialchars($blog['title']); ?></h2>
                            <ul class="blog-info-link mt-3 mb-4">
                                <li><i class="fa fa-user"></i> <?php echo htmlspecialchars($blog['author']); ?></li>
                                <li><i class="fa fa-tags"></i> <?php echo htmlspecialchars($blog['category']); ?></li>
                            </ul>
                            <div class="share-container mb-4">
    <button id="shareButton" class="btn btn-primary"><i class="fa fa-share-alt"></i> Share this Post</button>
</div>
                            <?php echo $blog['content']; ?>
                        </div>
                    </div>

                    <?php include 'includes/blog_comments.php'; ?>
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
    const shareButton = document.getElementById('shareButton');
    const shareContainer = document.querySelector('.share-container');

    const pageUrl = window.location.href;
    const pageTitle = document.title;
    const shareData = {
        title: pageTitle,
        text: 'Check out this page: ' + pageTitle,
        url: pageUrl
    };

    if (navigator.share) {
        // If Web Share API is supported, use it.
        shareButton.addEventListener('click', async () => {
            try {
                await navigator.share(shareData);
                console.log('Content shared successfully');
            } catch (err) {
                console.log('Error sharing:', err.message);
            }
        });
    } else {
        // If not supported, show the fallback links instead.
        const fallbackHtml = `
            <div class="d-flex align-items-center">
                <span class="d-inline-block me-2">Share this page:</span>
                <a href="https://wa.me/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}" target="_blank" title="Share on WhatsApp"><i class="fa fa-whatsapp fa-2x"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.url)}" target="_blank" title="Share on Facebook"><i class="fa fa-facebook fa-2x"></i></a>
                <a href="https://www.instagram.com/direct/inbox/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}" target="_blank" title="Share on Instagram"><i class="fa fa-instagram fa-2x"></i></a>
            </div>
        `;
        shareContainer.innerHTML = fallbackHtml;
    }
});
</script>
</body>
</html>
