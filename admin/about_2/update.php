<?php
include '../session.php';
include '../db.php';

$id = intval($_POST['id']);
$title = $_POST['title'];
$heading = $_POST['heading'];
$description = $_POST['description'];

$image1 = $_POST['existing_image1'] ?? '';
$image2 = $_POST['existing_image2'] ?? '';

// Replace image1 if uploaded
if (!empty($_FILES['image1']['name'])) {
    $target1 = 'uploads/' . time() . '_' . basename($_FILES['image1']['name']);
    move_uploaded_file($_FILES['image1']['tmp_name'], '../' . $target1);
    $image1 = $target1;
}

// Replace image2 if uploaded
if (!empty($_FILES['image2']['name'])) {
    $target2 = 'uploads/' . time() . '_' . basename($_FILES['image2']['name']);
    move_uploaded_file($_FILES['image2']['tmp_name'], '../' . $target2);
    $image2 = $target2;
}

$stmt = $conn->prepare("UPDATE about_2 SET title=?, heading=?, description=?, image1=?, image2=? WHERE id=?");
$stmt->bind_param("sssssi", $title, $heading, $description, $image1, $image2, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
