<?php
include '../session.php';
include '../db.php';
include '../includes/helpers.php'; // for clean_input, strip tags, etc.

try {
    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $content   = trim($_POST['content'] ?? '');
    $excerpt   = trim($_POST['excerpt'] ?? '');
    $category  = trim($_POST['category'] ?? '');
    $tags      = trim($_POST['tags'] ?? '');
    $author    = trim($_POST['author'] ?? '');

    if (!$title || !$content) {
        throw new Exception("Title and Content are required.");
    }

    // Auto-generate slug if empty
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    // Handle image upload - always save relative path only
    $featured_image = null;
if (!empty($_FILES['featured_image']['name']) && is_uploaded_file($_FILES['featured_image']['tmp_name'])) {
    $fileName   = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['featured_image']['name']));
    $targetPath = 'uploads/blogs/' . $fileName; // stored in DB (relative)
    $fullPath   = __DIR__ . '/../' . $targetPath; // actual location to save

    if (!is_dir(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0777, true);
    }

    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $fullPath)) {
        $featured_image = $targetPath;
    }
}


    $stmt = $conn->prepare("
        INSERT INTO blogs 
        (title, slug, content, excerpt, category, tags, author, featured_image, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param("ssssssss", $title, $slug, $content, $excerpt, $category, $tags, $author, $featured_image);
    $stmt->execute();

    // Redirect back to the blog list with success flag
    header("Location: ../allBlogs.php?success=1");
    exit;

} catch (Exception $e) {
    // Redirect with error message
    header("Location: ../allBlogs.php?error=" . urlencode($e->getMessage()));
    exit;
}
