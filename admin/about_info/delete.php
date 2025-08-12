<?php
// admin/about_info/delete.php
include '../session.php';
include '../includes/db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if(!$id){ echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }

mysqli_query($conn, "DELETE FROM about_info WHERE id = $id");
echo json_encode(['success'=>true]);
