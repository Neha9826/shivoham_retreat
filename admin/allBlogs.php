<?php
// admin/allBlogs.php
include 'session.php';
include 'db.php';

// Put this in allBlogs.php after includes (session/db) and BEFORE any HTML output.
function blog_image_url(?string $dbPath, string $siteBase = ''): string {
    // Fallback placeholder
    $placeholder = rtrim($siteBase, '/').'/uploads/no-image.jpg';

    if (!$dbPath) return $placeholder;

    // Absolute URL? just return it.
    if (preg_match('#^https?://#i', $dbPath)) return $dbPath;

    // Normalize
    $p = str_replace('\\', '/', $dbPath);
    // Strip any leading ../
    while (strpos($p, '../') === 0) $p = substr($p, 3);
    // Remove leading slash
    $p = ltrim($p, '/');

    // Build filesystem base like: C:\xampp\htdocs\ShivohamRetreat
    $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $fsBase = $doc . rtrim($siteBase, '/');

    // Try both variants: with and without "admin/" prefix
    $relCandidates = [];

    if (strpos($p, 'admin/') === 0) {
        // Stored with admin/ — try as-is, then without admin/
        $relCandidates[] = $p;
        $relCandidates[] = substr($p, 6); // drop "admin/"
    } else {
        // Stored without admin/ — try as-is, then with admin/
        $relCandidates[] = $p;
        $relCandidates[] = 'admin/' . $p;
    }

    foreach ($relCandidates as $rel) {
        $fs = $fsBase . '/' . $rel;             // filesystem path
        if (file_exists($fs)) {
            return rtrim($siteBase, '/') . '/' . $rel; // web URL
        }
    }

    // If the file check fails (e.g., different env), still return a sane URL
    return rtrim($siteBase, '/') . '/' . $p;
}
// Fetch all blogs
$result = mysqli_query($conn, "SELECT * FROM blogs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>All Blogs</h2>
                <a href="addBlog.php" class="btn btn-primary">+ Add New Blog</a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Blog added successfully.</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="row">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
<?php
$imgUrl = blog_image_url($row['featured_image'], '');
?>
<img src="<?= htmlspecialchars($imgUrl) ?>" class="card-img-top" style="max-height:180px; object-fit:cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?= htmlspecialchars($row['category']) ?> | 
                                        By <?= htmlspecialchars($row['author']) ?> | 
                                        <?= date('d M Y', strtotime($row['created_at'])) ?>
                                    </small>
                                </p>
                                <p><?= htmlspecialchars(mb_strimwidth(strip_tags($row['excerpt'] ?: $row['content']), 0, 100, '...')) ?></p>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <a href="editBlog.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="blogs/delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this blog?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
