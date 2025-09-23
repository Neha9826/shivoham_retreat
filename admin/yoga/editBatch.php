<!DOCTYPE html>
<html lang="en">
<?php include '../../includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include '../../includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include '../../includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 mt-4">
                <h2>Edit Batch</h2>
                <?php
                include '../../../db.php';

                if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                    echo '<div class="alert alert-danger">Invalid batch ID.</div>';
                    exit;
                }

                $id = (int)$_GET['id'];

                // Fetch retreats for dropdown
                $retreats = $conn->query("SELECT id, title FROM y_retreats ORDER BY title ASC");

                // Fetch existing batch
                $stmt = $conn->prepare("SELECT * FROM y_batches WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $batch = $stmt->get_result()->fetch_assoc();

                if (!$batch) {
                    echo '<div class="alert alert-danger">Batch not found.</div>';
                    exit;
                }

                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    $retreat_id = intval($_POST['retreat_id']);
                    $name = $_POST['name'];
                    $start_date = $_POST['start_date'];
                    $end_date = $_POST['end_date'];
                    $capacity = intval($_POST['capacity']);

                    $stmt = $conn->prepare("UPDATE y_batches SET retreat_id = ?, name = ?, start_date = ?, end_date = ?, capacity = ? WHERE id = ?");
                    $stmt->bind_param("isssii", $retreat_id, $name, $start_date, $end_date, $capacity, $id);

                    if ($stmt->execute()) {
                        echo '<div class="alert alert-success">Batch updated successfully!</div>';
                        // Refresh data
                        $batch = array_merge($batch, [
                            'retreat_id' => $retreat_id,
                            'name' => $name,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'capacity' => $capacity
                        ]);
                    } else {
                        echo '<div class="alert alert-danger">Error updating batch.</div>';
                    }
                }
                ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Retreat</label>
                        <select name="retreat_id" class="form-select" required>
                            <option value="">Select Retreat</option>
                            <?php while ($r = $retreats->fetch_assoc()): ?>
                                <option value="<?= $r['id']; ?>" <?= ($batch['retreat_id'] == $r['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($r['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($batch['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($batch['start_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($batch['end_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($batch['capacity']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Batch</button>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
