<?php
// admin/addCancellationPolicy.php
include 'session.php';
include 'db.php';

// Fetch cancellation policies
$cancellationPolicyRs = mysqli_query($conn, "SELECT * FROM cancellation_policy ORDER BY id DESC");

// admin base URL
$ADMIN_BASE_URL = '/ShivohamRetreat/admin/';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>Manage Cancellation Policy</h2>

            <form id="form-cancellation" method="POST" action="cancellation_policy/handler.php">
                <div class="card mb-4" id="section-cancellation">
                    <div class="card-header">Cancellation Policy</div>
                    <div class="card-body">
                        <input type="hidden" name="action" id="cancellation-action" value="insert">
                        <input type="hidden" name="id" id="cancellation-id" value="">
                        <div class="mb-3">
                            <label>Cancellation Time Period</label>
                            <input type="text" name="time_period" id="cancellation-time-period" class="form-control" placeholder="e.g., 7 days before check-in" required>
                        </div>
                        <div class="mb-3">
                            <label>Refundable Amount (%)</label>
                            <input type="number" name="refundable_percentage" id="cancellation-refundable-percentage" class="form-control" required min="0" max="100">
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" name="save_cancellation" id="btn-save-cancellation" class="btn btn-primary">Save Policy</button>
                        <button type="button" id="btn-update-cancellation" class="btn btn-success" style="display:none;">Update Policy</button>
                        <button type="button" id="btn-cancel-cancellation" class="btn btn-secondary" style="display:none;">Cancel Edit</button>
                    </div>
                </div>
            </form>

            <div class="row mb-4">
                <?php
                if (mysqli_num_rows($cancellationPolicyRs) > 0) {
                    while ($cp = mysqli_fetch_assoc($cancellationPolicyRs)) {
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card p-2">
                                <h5 class="mb-2"><?= htmlspecialchars($cp['time_period']) ?></h5>
                                <p class="mb-2">Refundable: <?= htmlspecialchars($cp['refundable_percentage']) ?>%</p>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-cancellation" data-id="<?= $cp['id'] ?>">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-cancellation" data-id="<?= $cp['id'] ?>">Delete</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<div class='col-12'><p>No cancellation policies found.</p></div>";
                }
                ?>
            </div>

        </main>
    </div>
</div>

<script>
    // Utility function for AJAX requests
    async function sendRequest(url, method, body) {
        const options = { method: method };
        if (body) {
            options.body = body;
        }
        const res = await fetch(url, options);
        return res.json();
    }

    // Handle form submission for Insert/Update
    document.getElementById('form-cancellation').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const action = formData.get('action');

        const result = await sendRequest('cancellation_policy/handler.php', 'POST', formData);

        if (result.success) {
            alert('Policy ' + (action === 'insert' ? 'added' : 'updated') + ' successfully.');
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    });

    // NEW: Handle Update button click
    document.getElementById('btn-update-cancellation').addEventListener('click', async function() {
        const form = document.getElementById('form-cancellation');
        const formData = new FormData(form);
        formData.set('action', 'update');

        const result = await sendRequest('cancellation_policy/handler.php', 'POST', formData);

        if (result.success) {
            alert('Policy updated successfully.');
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    });

    // Handle Edit button click
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('btn-edit-cancellation')) {
            const id = e.target.getAttribute('data-id');
            const result = await sendRequest('cancellation_policy/handler.php?action=get&id=' + id, 'GET');

            if (result.success) {
                const policy = result.row;
                document.getElementById('cancellation-id').value = policy.id;
                document.getElementById('cancellation-time-period').value = policy.time_period;
                // Set the value as an integer
                document.getElementById('cancellation-refundable-percentage').value = parseInt(policy.refundable_percentage);
                
                // Toggle buttons
                document.getElementById('btn-save-cancellation').style.display = 'none';
                document.getElementById('btn-update-cancellation').style.display = 'inline-block';
                document.getElementById('btn-cancel-cancellation').style.display = 'inline-block';
                
                document.getElementById('section-cancellation').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Error fetching policy: ' + result.error);
            }
        }
    });

    // Handle Delete button click
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('btn-delete-cancellation')) {
            const id = e.target.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this policy?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                const result = await sendRequest('cancellation_policy/handler.php', 'POST', formData);

                if (result.success) {
                    alert('Policy deleted successfully.');
                    location.reload();
                } else {
                    alert('Error deleting policy: ' + result.error);
                }
            }
        }
    });
    
    // Handle Cancel button click
    document.getElementById('btn-cancel-cancellation').addEventListener('click', function() {
        // Reset the form and buttons
        document.getElementById('form-cancellation').reset();
        document.getElementById('cancellation-id').value = '';
        document.getElementById('cancellation-action').value = 'insert';
        document.getElementById('btn-save-cancellation').style.display = 'inline-block';
        document.getElementById('btn-update-cancellation').style.display = 'none';
        document.getElementById('btn-cancel-cancellation').style.display = 'none';
    });
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>