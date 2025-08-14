<?php
include '../session.php';
include '../db.php';
include '../includes/helpers.php';

header('Content-Type: application/json');

try {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $existing_featured_image = $_POST['existing_featured_image'] ?? '';

    if (!$id || !$title || !$content) {
        throw new Exception("ID, Title and Content are required.");
    }

    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    $featured_image = $existing_featured_image;
    if (!empty($_FILES['featured_image']['name'])) {
        $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
        $targetPath = 'uploads/blogs/' . $fileName;
        $fullPath = '../' . $targetPath;
        if (!is_dir('../uploads/blogs')) mkdir('../uploads/blogs', 0777, true);
        move_uploaded_file($_FILES['featured_image']['tmp_name'], $fullPath);
        $featured_image = $targetPath;
    }

    $stmt = $conn->prepare("UPDATE blogs SET title=?, slug=?, content=?, excerpt=?, category=?, tags=?, author=?, featured_image=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("ssssssssi", $title, $slug, $content, $excerpt, $category, $tags, $author, $featured_image, $id);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
