<?php
include 'session.php';
include 'db.php';

// Delete message
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contact_messages WHERE id = $id");
    header("Location: contact_messages.php?deleted=1");
    exit;
}

$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container mt-4">
            <h2>Contact Messages</h2>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Message deleted successfully!</div>
            <?php endif; ?>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                            <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
