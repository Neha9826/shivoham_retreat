<?php
include '../session.php';
include '../db.php';

$id = intval($_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM about_2 WHERE id = $id");
if ($row = mysqli_fetch_assoc($res)) {
    echo json_encode(['success' => true, 'row' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Record not found']);
}
