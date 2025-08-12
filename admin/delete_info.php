<?php
include 'session.php';
include 'db.php';
$id = intval($_GET['id']);
mysqli_query($conn, "DELETE FROM about_info WHERE id=$id");
echo "Deleted";
