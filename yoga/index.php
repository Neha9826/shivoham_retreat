<?php
session_start();
include __DIR__ . '/../config.php';   // ensures BASE_URL and YOGA_URL are defined
include __DIR__ . '/../db.php';

// Read filters from GET
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$locations = isset($_GET['location']) ? (array)$_GET['location'] : [];
$durations = isset($_GET['duration']) ? (array)$_GET['duration'] : [];
$price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Build WHERE clauses (safe escaping)
$where = [];
if ($q !== '') {
    $q_esc = $conn->real_escape_string($q);
    $where[] = "(name LIKE '%$q_esc%' OR description LIKE '%$q_esc%' OR location LIKE '%$q_esc%')";
}
if (count($locations) > 0) {
    $escaped = array_map(function($v) use ($conn) { return "'" . $conn->real_escape_string($v) . "'"; }, $locations);
    $where[] = "location IN (" . implode(',', $escaped) . ")";
}
if (count($durations) > 0) {
    $escaped = array_map(function($v) use ($conn) { return (int)$v; }, $durations);
    $where[] = "duration_days IN (" . implode(',', $escaped) . ")";
}
if ($price_min > 0) {
    $where[] = "price_from >= " . (int)$price_min;
}
if ($price_max > 0) {
    $where[] = "price_from <= " . (int)$price_max;
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) AS cnt FROM yoga_retreats $whereSql";
$countRes = $conn->query($countSql);
$total = ($countRes && $countRes->num_rows) ? (int)$countRes->fetch_assoc()['cnt'] : 0;
$pages = max(1, ceil($total / $perPage));

// Fetch retreats
$sql = "SELECT * FROM yoga_retreats $whereSql ORDER BY id DESC LIMIT $offset, $perPage";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <meta charset="utf-8">
    <title>Yoga Retreats | Shivoham Retreat</title>

    <!-- yoga CSS (module overrides + layout) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
</head>
<body class="yoga-page">

    <!-- main site header (kept, but yoga.css will override visuals) -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- optional social bar -->
    <?php include __DIR__ . '/../includes/fixed_social_bar.php'; ?>

    <!-- yoga sub-navbar (search + account) -->
    <?php include __DIR__ . '/yoga_navbar.php'; ?>
    <?php include __DIR__ . '/videoBanner.php'; ?>

    <main class="py-4">
      <div class="container">
        <div class="row">
          <!-- FILTERS (left) -->
          <aside class="col-lg-3 mb-4">
            <div class="card filter-card">
              <div class="card-body">
                <h5 class="mb-3">Refine search</h5>
                <form id="filterForm" method="get" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>">
                  <div class="mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, location...">
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Location</label>
                    <?php
                    // list distinct locations
                    $locs = $conn->query("SELECT DISTINCT location FROM yoga_retreats WHERE location<>'' ORDER BY location");
                    while ($l = $locs->fetch_assoc()):
                        $val = $l['location'];
                        $checked = in_array($val, $locations) ? 'checked' : '';
                    ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="location[]" id="loc_<?= md5($val) ?>" value="<?= htmlspecialchars($val) ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="loc_<?= md5($val) ?>"><?= htmlspecialchars($val) ?></label>
                      </div>
                    <?php endwhile; ?>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Duration (days)</label>
                    <?php
                    $dres = $conn->query("SELECT DISTINCT duration_days FROM yoga_retreats WHERE duration_days IS NOT NULL ORDER BY duration_days");
                    while ($d = $dres->fetch_assoc()):
                      $dv = (int)$d['duration_days'];
                      $checked = in_array((string)$dv, $durations) ? 'checked' : '';
                    ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="duration[]" id="dur_<?= $dv ?>" value="<?= $dv ?>" <?= $checked ?>>
                        <label class="form-check-label small" for="dur_<?= $dv ?>"><?= $dv ?> days</label>
                      </div>
                    <?php endwhile; ?>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Price from</label>
                    <input type="number" name="price_min" class="form-control" placeholder="Min" value="<?= $price_min ?: '' ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Price to</label>
                    <input type="number" name="price_max" class="form-control" placeholder="Max" value="<?= $price_max ?: '' ?>">
                  </div>

                  <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                    <a href="<?= YOGA_URL ?>index.php" class="btn btn-link small mt-2">Reset filters</a>
                  </div>
                </form>
              </div>
            </div>
          </aside>

          <!-- RESULTS (right) -->
          <section class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div><strong><?= $total ?></strong> retreats found</div>
              <div>
                <form method="get" id="sortForm" class="d-flex align-items-center">
                  <!-- keep q and filters in sortForm -->
                  <?php
                  // persist current filters as hidden fields
                  foreach ($locations as $loc) {
                    echo '<input type="hidden" name="location[]" value="' . htmlspecialchars($loc) . '">';
                  }
                  foreach ($durations as $d) {
                    echo '<input type="hidden" name="duration[]" value="' . htmlspecialchars($d) . '">';
                  }
                  if ($price_min) echo '<input type="hidden" name="price_min" value="' . (int)$price_min . '">';
                  if ($price_max) echo '<input type="hidden" name="price_max" value="' . (int)$price_max . '">';
                  if ($q) echo '<input type="hidden" name="q" value="' . htmlspecialchars($q) . '">';
                  ?>
                  <label class="me-2 small">Sort</label>
                  <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Newest</option>
                    <option value="price_asc">Price low→high</option>
                    <option value="price_desc">Price high→low</option>
                  </select>
                </form>
              </div>
            </div>

            <div class="row g-4">
              <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($row = $res->fetch_assoc()): ?>
                  <div class="col-md-6 col-lg-4">
                    <div class="card retreat-card h-100">
                      <div class="retreat-thumb">
                        <img src="<?= BASE_URL ?>uploads/yoga/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="img-fluid w-100">
                      </div>
                      <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                        <div class="small text-muted mb-2"><?= htmlspecialchars($row['location']) ?> • <?= (int)$row['duration_days'] ?> days</div>
                        <p class="card-text"><?= nl2br(htmlspecialchars(substr(strip_tags($row['description']), 0, 120))) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                          <div class="price">
                            <strong>₹<?= number_format((int)$row['price_from']) ?></strong> <small class="text-muted">from</small>
                          </div>
                          <div>
                            <a href="retreat.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">View</a>
                            <a href="<?= YOGA_URL ?>register.php" class="btn btn-primary btn-sm ms-2">Book</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="col-12">
                  <div class="alert alert-warning">No retreats match your filters.</div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
              <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination">
                  <?php for ($p = 1; $p <= $pages; $p++): 
                    // build page URL preserving GET params
                    $qs = $_GET; $qs['page'] = $p; $link = basename($_SERVER['PHP_SELF']) . '?' . http_build_query($qs);
                  ?>
                    <li class="page-item <?= $p == $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($link) ?>"><?= $p ?></a></li>
                  <?php endfor; ?>
                </ul>
              </nav>
            <?php endif; ?>

          </section>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- Form (modal booking) -->
    <?php include __DIR__ . '/../includes/form.php'; ?>

    <!-- Scripts (use BASE_URL for assets) -->
    <script src="<?= BASE_URL ?>js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/owl.carousel.min.js"></script>
    <script>
    // mobile filter toggle (optional)
    function toggleFilters() {
      document.querySelector('.filter-card').classList.toggle('open');
    }
    // auto-submit filter when checkboxes changed (optional)
    document.querySelectorAll('#filterForm .form-check-input').forEach(function(el){
      el.addEventListener('change', function(){ /* keep manual submit to avoid accidental reload */ });
    });
    </script>
</body>
</html>
