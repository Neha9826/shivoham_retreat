<?php include 'session.php'; ?>
<?php include 'db.php';

if (isset($_GET['id'])) {
    $emp_id = intval($_GET['id']);

    $deleteQuery = "DELETE FROM emp WHERE id=$emp_id";
    mysqli_query($conn, $deleteQuery);
}

header("Location: allEmp.php");
exit;
?>
