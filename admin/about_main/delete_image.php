<?php
// admin/about_main/delete_image.php
include '../session.php';
include '../db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if(!$id){ echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }

$res = mysqli_query($conn, "SELECT * FROM about_1 WHERE id = $id LIMIT 1");
if($res && mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
    if(!empty($row['main_image1']) && file_exists('../' . $row['main_image1'])) @unlink('../' . $row['main_image1']);
    if(!empty($row['main_image2']) && file_exists('../' . $row['main_image2'])) @unlink('../' . $row['main_image2']);
}
mysqli_query($conn, "DELETE FROM about_1 WHERE id = $id");
echo json_encode(['success'=>true]);
