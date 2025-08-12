<?php
// admin/about_main/update.php
include '../session.php';
include '../db.php';

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if(!$id){
    echo json_encode(['success'=>false,'error'=>'Missing id']); exit;
}

$title = mysqli_real_escape_string($conn, $_POST['main_heading'] ?? '');
$desc  = mysqli_real_escape_string($conn, $_POST['main_description'] ?? '');

// fetch existing
$rowRs = mysqli_query($conn, "SELECT * FROM about_1 WHERE id = $id LIMIT 1");
if(mysqli_num_rows($rowRs)==0){
    echo json_encode(['success'=>false,'error'=>'Record not found']); exit;
}
$row = mysqli_fetch_assoc($rowRs);

$img1 = $row['main_image1'];
$img2 = $row['main_image2'];

// handle new uploads
$mainDir = '../uploads/about/';
if (!is_dir($mainDir)) mkdir($mainDir, 0777, true);

if (!empty($_FILES['main_image1']['name'])) {
    // delete old file if present
    if(!empty($img1) && file_exists('../' . $img1)) @unlink('../' . $img1);
    $img1 = 'uploads/about/' . time() . '_' . basename($_FILES['main_image1']['name']);
    move_uploaded_file($_FILES['main_image1']['tmp_name'], '../' . $img1);
}
if (!empty($_FILES['main_image2']['name'])) {
    if(!empty($img2) && file_exists('../' . $img2)) @unlink('../' . $img2);
    $img2 = 'uploads/about/' . time() . '_' . basename($_FILES['main_image2']['name']);
    move_uploaded_file($_FILES['main_image2']['tmp_name'], '../' . $img2);
}

$stmt = $conn->prepare("UPDATE about_1 SET main_heading=?, main_description=?, main_image1=?, main_image2=? WHERE id=?");
$stmt->bind_param('ssssi', $title, $desc, $img1, $img2, $id);
$ok = $stmt->execute();

echo json_encode(['success'=>!!$ok]);
