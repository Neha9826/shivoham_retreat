<?php include 'session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Client Queries</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Client Queries</li>
                </ol>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Query Requests List
                    </div>
                    <div class="card-body">
                        <table id="datatablesSimple">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Room</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Adults</th>
                                    <th>Children</th>
                                    <th>Message</th>
                                    <th>Request Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include 'db.php';
                                $query = "SELECT br.*, r.room_name 
                                          FROM booking_requests br 
                                          LEFT JOIN rooms r ON br.room_id = r.id 
                                          ORDER BY br.id DESC";
                                $result = mysqli_query($conn, $query);

                                while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                    <tr>
                                        <td><?= $row['id']; ?></td>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= htmlspecialchars($row['phone']); ?></td>
                                        <td><?= htmlspecialchars($row['room_name']); ?></td>
                                        <td><?= $row['check_in']; ?></td>
                                        <td><?= $row['check_out']; ?></td>
                                        <td><?= $row['adults']; ?></td>
                                        <td><?= $row['children']; ?></td>
                                        <td><?= nl2br(htmlspecialchars($row['message'])); ?></td>
                                        <td><?= $row['request_date']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
</body>
</html>
