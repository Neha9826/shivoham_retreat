<?php
// admin/about_info/insert.php
include '../session.php';
include '../db.php';
header('Content-Type: text/html; charset=utf-8');

// Expect arrays: info_id[] (optional blank for new), info_title[], info_description[]
$ids = $_POST['info_id'] ?? [];
$titles = $_POST['info_title'] ?? [];
$descs = $_POST['info_description'] ?? [];

for($i=0;$i<count($titles);$i++){
    $id = isset($ids[$i]) && $ids[$i] ? intval($ids[$i]) : 0;
    $title = mysqli_real_escape_string($conn, $titles[$i]);
    $desc  = mysqli_real_escape_string($conn, $descs[$i]);
    if($id){
        // update existing
        $stmt = $conn->prepare("UPDATE about_info SET info_title=?, info_description=? WHERE id=?");
        $stmt->bind_param('ssi', $title, $desc, $id);
        $stmt->execute();
    } else {
        // insert new
        $stmt = $conn->prepare("INSERT INTO about_info (info_title, info_description, image) VALUES (?, ?, '')");
        $stmt->bind_param('ss', $title, $desc);
        $stmt->execute();
    }
}
// After saving, redirect back
header('Location: ../addAbout.php#section-info');
exit;
