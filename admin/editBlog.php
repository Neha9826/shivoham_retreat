<?php
// admin/editBlog.php
include 'session.php';
include 'db.php';
include 'includes/helpers.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: allBlogs.php?error=" . urlencode("Invalid blog ID"));
    exit;
}

// Fetch existing blog
$stmt = $conn->prepare("SELECT * FROM blogs WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();
if (!$blog) {
    header("Location: allBlogs.php?error=" . urlencode("Blog not found"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $author = trim($_POST['author'] ?? '');

        if (!$title || !$content) {
            throw new Exception("Title and Content are required.");
        }

        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $slug = trim($slug, '-');
        }

        // Handle image upload (store same format as insert.php)
        $featured_image = $blog['featured_image']; // keep old by default
        if (!empty($_FILES['featured_image']['name'])) {
            $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
            $targetPath = 'uploads/blogs/' . $fileName; // stored in DB
            $fullPath = '../' . $targetPath; // actual filesystem path

            if (!is_dir('../uploads/blogs')) {
                mkdir('../uploads/blogs', 0777, true);
            }
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $fullPath)) {
                $featured_image = $targetPath;
            }
        }

        $stmt = $conn->prepare("UPDATE blogs SET title=?, slug=?, content=?, excerpt=?, category=?, tags=?, author=?, featured_image=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssssssi", $title, $slug, $content, $excerpt, $category, $tags, $author, $featured_image, $id);
        $stmt->execute();

        header("Location: allBlogs.php?success=1");
        exit;
    } catch (Exception $e) {
        header("Location: editBlog.php?id=$id&error=" . urlencode($e->getMessage()));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<!-- Load CKEditor -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>Edit Blog</h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($blog['title']) ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($blog['slug']) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Content</label>
                    <textarea name="content" id="contentEditor" class="form-control" rows="8"><?= htmlspecialchars($blog['content']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($blog['excerpt']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Category</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($blog['category']) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Tags</label>
                    <input type="text" name="tags" value="<?= htmlspecialchars($blog['tags']) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Author</label>
                    <input type="text" name="author" value="<?= htmlspecialchars($blog['author']) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Featured Image</label><br>
                    <?php if (!empty($blog['featured_image'])): ?>
                        <img src="<?= htmlspecialchars($blog['featured_image']) ?>" alt="" style="max-width:150px; display:block; margin-bottom:10px;">
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Blog</button>
                <a href="allBlogs.php" class="btn btn-secondary">Cancel</a>
            </form>
        </main>
    </div>
</div>

<script>
    CKEDITOR.replace('contentEditor');
</script>

<?php include 'includes/script.php'; ?>
</body>
</html>
