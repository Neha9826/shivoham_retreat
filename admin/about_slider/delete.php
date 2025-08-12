<?php
// admin/about_slider/delete.php
include '../session.php';
include '../db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if(!$id){ echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }

$res = mysqli_query($conn, "SELECT image FROM about_slider WHERE id = $id LIMIT 1");
if($res && mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
    if(!empty($row['image']) && file_exists('../'.$row['image'])) @unlink('../'.$row['image']);
}
mysqli_query($conn, "DELETE FROM about_slider WHERE id = $id");
echo json_encode(['success'=>true]);
