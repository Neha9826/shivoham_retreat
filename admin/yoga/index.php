<?php 
// include '../../session.php'; 
include 'db.php';
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
                <h2>Yoga Module Dashboard</h2>
                <p class="mb-4 text-muted">Hover over a module to see available actions.</p>

                <div class="row g-4">
                    <?php
                    // Database connection assumed via included files
                    // Define modules with icons, colors, and DB table for count
                    $modules = [
                        'Organization' => [
                            'icon' => '🏢',
                            'color' => '#150dffff',
                            'table' => 'organizations',
                            'actions' => [
                                ['label' => 'All Organizations', 'link' => 'organization/manageOrganizations.php'],
                                ['label' => 'Create Organization', 'link' => 'organization/createOrganization.php']
                            ]
                        ],
                        'Retreats' => [
                            'icon' => '🏕️',
                            'color' => '#f28b82',
                            'table' => 'retreats',
                            'actions' => [
                                ['label' => 'All Retreats', 'link' => 'allRetreats.php'],
                                ['label' => 'Create Retreat', 'link' => 'createRetreat.php']
                            ]
                        ],
                        'Packages' => [
                            'icon' => '🎁',
                            'color' => '#fbbc04',
                            'table' => 'packages',
                            'actions' => [
                                ['label' => 'Manage Packages', 'link' => 'managePackages.php']
                            ]
                        ],
                        'Instructors' => [
                            'icon' => '🧘‍♂️',
                            'color' => '#34a853',
                            'table' => 'instructors',
                            'actions' => [
                                ['label' => 'Manage Instructors', 'link' => 'manageInstructors.php']
                            ]
                        ],
                        'Amenities' => [
                            'icon' => '🏠',
                            'color' => '#4285f4',
                            'table' => 'amenities',
                            'actions' => [
                                ['label' => 'Manage Amenities', 'link' => 'manageAmenities.php']
                            ]
                        ],
                        'Batches' => [
                            'icon' => '📅',
                            'color' => '#aa00ff',
                            'table' => 'batches',
                            'actions' => [
                                ['label' => 'All Batches', 'link' => 'allBatches.php'],
                                ['label' => 'Create Batch', 'link' => 'createBatch.php']
                            ]
                        ],
                        'Bookings' => [
                            'icon' => '📝',
                            'color' => '#00c0ff',
                            'table' => 'bookings',
                            'actions' => [
                                ['label' => 'All Bookings', 'link' => 'bookings/allBookings.php']
                            ]
                        ],
                        'Users' => [
                            'icon' => '👤',
                            'color' => '#ff6d01',
                            'table' => 'users',
                            'actions' => [
                                ['label' => 'All Users', 'link' => 'users/allUsers.php'],
                                ['label' => 'Create User', 'link' => 'users/createUser.php']
                            ]
                        ]
                    ];

                    foreach ($modules as $name => $module):
                        // Fetch count dynamically from DB
                        $count = 0;
                        if (!empty($module['table'])) {
                            $result = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `yoga_retreats`");
                            if ($result) {
                                $row = mysqli_fetch_assoc($result);
                                $count = $row['cnt'];
                            }
                        }
                    ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm module-card" style="position: relative; border-left: 6px solid <?= $module['color'] ?>;">
                                <div class="card-body text-center">
                                    <div style="font-size: 2rem;"><?= $module['icon'] ?></div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($name) ?></h5>
                                    <p class="text-muted mb-0"><?= $count ?> items</p>
                                </div>
                                <ul class="list-group list-group-flush action-menu" style="
                                    display: none;
                                    position: absolute;
                                    top: 0; left: 0;
                                    width: 100%;
                                    background: rgba(255,255,255,0.95);
                                    border-radius: .5rem;
                                    z-index: 10;
                                    padding: .5rem 0;">
                                    <?php foreach ($module['actions'] as $a): ?>
                                        <li class="list-group-item text-center">
                                            <a href="<?= htmlspecialchars($a['link']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($a['label']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>
<?php include '../includes/script.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script>
document.querySelectorAll('.module-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.querySelector('.action-menu').style.display = 'block';
    });
    card.addEventListener('mouseleave', () => {
        card.querySelector('.action-menu').style.display = 'none';
    });
});
</script>
</body>
</html>
