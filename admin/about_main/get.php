<?php
// admin/about_main/get.php
include '../session.php';
include '../db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if(!$id){ echo json_encode(['success'=>false]); exit; }

$res = mysqli_query($conn, "SELECT * FROM about_1 WHERE id = $id LIMIT 1");
if(!$res || mysqli_num_rows($res)==0){ echo json_encode(['success'=>false]); exit; }
$row = mysqli_fetch_assoc($res);
echo json_encode(['success'=>true, 'row'=>$row]);
