<?php
// admin/about_main/insert.php
include '../session.php';
include '../db.php';

$mainDir = '../uploads/about/';
if (!is_dir($mainDir)) mkdir($mainDir, 0777, true);

$title = mysqli_real_escape_string($conn, $_POST['main_heading'] ?? '');
$desc  = mysqli_real_escape_string($conn, $_POST['main_description'] ?? '');

// files
$img1 = $_POST['existing_main_image1'] ?? '';
$img2 = $_POST['existing_main_image2'] ?? '';

if (!empty($_FILES['main_image1']['name'])) {
    $img1 = 'uploads/about/' . time() . '_' . basename($_FILES['main_image1']['name']);
    move_uploaded_file($_FILES['main_image1']['tmp_name'], '../' . $img1);
}
if (!empty($_FILES['main_image2']['name'])) {
    $img2 = 'uploads/about/' . time() . '_' . basename($_FILES['main_image2']['name']);
    move_uploaded_file($_FILES['main_image2']['tmp_name'], '../' . $img2);
}

// Insert — allow multiple records; you can truncate if you want single record only
$stmt = $conn->prepare("INSERT INTO about_1 (main_heading, main_description, main_image1, main_image2) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $title, $desc, $img1, $img2);
$stmt->execute();

header('Location: ../addAbout.php#section-main');
exit;
