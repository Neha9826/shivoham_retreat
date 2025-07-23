<?php
include 'session.php';
include 'db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $delete = "DELETE FROM extra_bed_rates WHERE id = $id";
    if (mysqli_query($conn, $delete)) {
        header("Location: extraBedList.php");
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    header("Location: extraBedList.php");
    exit();
}
?>
