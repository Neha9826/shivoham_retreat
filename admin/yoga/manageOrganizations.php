<?php
// include '../../session.php';
include 'db.php';

// Fetch all organizations
$orgs = $conn->query("SELECT * FROM organizations ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/head.php'; ?>
<link href="../css/styles.css" rel="stylesheet">
<body class="sb-nav-fixed">
<?php include '../includes/navbar.php'; ?>
<div id="layoutSidenav">
<?php include '../includes/sidebar.php'; ?>
<div id="layoutSidenav_content">
<main>
<div class="container-fluid px-4 mt-4">
<h2>Manage Organizations</h2>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<a href="createOrganization.php" class="btn btn-primary mb-3">Add New Organization</a>

<div class="card mb-4">
<div class="card-body">
<table class="table table-bordered">
<thead>
<tr>
<th>Name</th>
<th>Slug</th>
<th>Contact Email</th>
<th>City</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if ($orgs && $orgs->num_rows > 0): ?>
    <?php while ($org = $orgs->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($org['name']); ?></td>
        <td><?= htmlspecialchars($org['slug']); ?></td>
        <td><?= htmlspecialchars($org['contact_email']); ?></td>
        <td><?= htmlspecialchars($org['city']); ?></td>
        <td>
            <a href="editOrganization.php?id=<?= $org['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
            <a href="deleteOrganization.php?id=<?= $org['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this organization?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5">No organizations found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</div></div>
</body>
</html>
