<?php
include '../session.php';
include '../db.php';

$id = intval($_GET['id']);
if (mysqli_query($conn, "DELETE FROM about_2 WHERE id = $id")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
