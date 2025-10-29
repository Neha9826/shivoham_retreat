<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['pending', 'contacted', 'converted'];
if (!$id || !in_array($status, $allowed)) {
    echo json_encode(['success'=>false, 'msg'=>'Invalid input']);
    exit;
}

$stmt = $conn->prepare("UPDATE y_query SET host_status=? WHERE id=?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'msg'=>$stmt->error]);
}
$stmt->close();
