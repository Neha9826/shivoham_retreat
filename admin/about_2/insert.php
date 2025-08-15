<?php
include '../session.php';
include '../db.php';

$title = $_POST['title'];
$heading = $_POST['heading'];
$description = $_POST['description'];

$image1 = '';
$image2 = '';

if (!empty($_FILES['image1']['name'])) {
    $target1 = 'uploads/' . time() . '_' . basename($_FILES['image1']['name']);
    move_uploaded_file($_FILES['image1']['tmp_name'], '../' . $target1);
    $image1 = $target1;
}
if (!empty($_FILES['image2']['name'])) {
    $target2 = 'uploads/' . time() . '_' . basename($_FILES['image2']['name']);
    move_uploaded_file($_FILES['image2']['tmp_name'], '../' . $target2);
    $image2 = $target2;
}

$stmt = $conn->prepare("INSERT INTO about_2 (title, heading, description, image1, image2) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $title, $heading, $description, $image1, $image2);

if ($stmt->execute()) {
    header("Location: ../addAbout.php");
} else {
    echo "Error: " . $stmt->error;
}
