<?php
include 'session.php';
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM meal_plan WHERE id=$id";
    mysqli_query($conn, $query);
}

header("Location: allMeals.php");
exit;
?>
