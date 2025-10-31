<?php
require_once __DIR__ . '/yoga_session.php';

include __DIR__ . '/../config.php';
include __DIR__ . '/../db.php';

// --- Read filters from GET (ensure types and defaults)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$locations = isset($_GET['location']) ? (array) $_GET['location'] : [];
$durations = isset($_GET['duration']) ? (array) $_GET['duration'] : [];
$price_min = isset($_GET['price_min']) ? (int) $_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int) $_GET['price_max'] : 0;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// --- Build WHERE array (use proper table aliases)
$where = []; // IMPORTANT: initialize to avoid undefined variable errors

if ($q !== '') {
    $q_esc = $conn->real_escape_string($q);
    // search packages, retreats, organization names and country
    $where[] = "(p.title LIKE '%$q_esc%' OR p.description LIKE '%$q_esc%' OR r.title LIKE '%$q_esc%' OR o.name LIKE '%$q_esc%' OR o.country LIKE '%$q_esc%')";
}

// location filter: we use organizations.country as "location"
if (count($locations) > 0) {
    $escaped = array_map(function($v) use ($conn) {
        return "'" . $conn->real_escape_string($v) . "'";
    }, $locations);
    $where[] = "o.country IN (" . implode(',', $escaped) . ")";
}

// duration filter: use p.nights
if (count($durations) > 0) {
    $escaped = array_map(function($v) { return (int)$v; }, $durations);
    $where[] = "p.nights IN (" . implode(',', $escaped) . ")";
}

// price filters: based on package base price (price_per_person)
if ($price_min > 0) {
    $where[] = "p.price_per_person >= " . (int)$price_min;
}
if ($price_max > 0) {
    $where[] = "p.price_per_person <= " . (int)$price_max;
}

// Compose WHERE SQL
$whereSql = '';
if (count($where) > 0) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

// --- Count total matching packages for pagination
$countSql = "
  SELECT COUNT(*) AS cnt
  FROM yoga_packages p
  JOIN yoga_retreats r ON p.retreat_id = r.id
  JOIN organizations o ON r.organization_id = o.id
  WHERE p.is_published = 1 AND r.is_published = 1
  " . (count($where) > 0 ? " AND " . implode(' AND ', $where) : '');

$countRes = $conn->query($countSql);
$total = ($countRes && $countRes->num_rows) ? (int)$countRes->fetch_assoc()['cnt'] : 0;
$pages = max(1, ceil($total / $perPage));

// --- Fetch paginated packages
$sql = "
  SELECT
    p.id,
    p.title AS package_title,
    p.description,
    p.price_per_person,
    p.nights,
    p.meals_included,
    r.id AS retreat_id,
    r.title AS retreat_title,
    o.id AS org_id,
    o.name AS org_name,
    o.country,
    (SELECT image_path FROM yoga_retreat_images WHERE retreat_id = r.id LIMIT 1) AS image_path
  FROM yoga_packages p
  JOIN yoga_retreats r ON p.retreat_id = r.id
  JOIN organizations o ON r.organization_id = o.id
  WHERE p.is_published = 1 AND r.is_published = 1
  " . (count($where) > 0 ? " AND " . implode(' AND ', $where) : '') . "
  ORDER BY p.created_at DESC
  LIMIT $offset, $perPage
";

$res = $conn->query($sql);

// --- Prepare data for sidebar filters (distinct countries and nights)
$locs = $conn->query("SELECT DISTINCT country FROM organizations WHERE country<>'' ORDER BY country ASC");
$dres = $conn->query("SELECT DISTINCT nights FROM yoga_packages WHERE nights > 0 ORDER BY nights ASC");
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
                    $locs = $conn->query("SELECT DISTINCT country FROM organizations WHERE country<>'' ORDER BY country");
                    if ($locs && $locs->num_rows > 0):
                        while ($l = $locs->fetch_assoc()):
                            $val = $l['country'];
                            $checked = in_array($val, $locations) ? 'checked' : '';
                    ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="location[]" id="loc_<?= md5($val) ?>" value="<?= htmlspecialchars($val) ?>" <?= $checked ?>>
                          <label class="form-check-label small" for="loc_<?= md5($val) ?>"><?= htmlspecialchars($val) ?></label>
                        </div>
                    <?php endwhile; else: ?>
                        <p class="text-muted small">No countries found</p>
                    <?php endif; ?>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Duration (nights)</label>
                    <?php
                    $dres = $conn->query("SELECT DISTINCT nights FROM yoga_packages WHERE nights > 0 ORDER BY nights ASC");
                    if ($dres && $dres->num_rows > 0):
                        while ($d = $dres->fetch_assoc()):
                            $dv = (int)$d['nights'];
                            $checked = in_array((string)$dv, $durations) ? 'checked' : '';
                    ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="duration[]" id="dur_<?= $dv ?>" value="<?= $dv ?>" <?= $checked ?>>
                          <label class="form-check-label small" for="dur_<?= $dv ?>"><?= $dv ?> nights</label>
                        </div>
                    <?php endwhile; else: ?>
                        <p class="text-muted small">No durations available</p>
                    <?php endif; ?>
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
              <!-- Mobile Filter Button -->
<div class="d-lg-none mb-3 text-end">
  <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
    <i class="bi bi-funnel"></i> Filter
  </button>
</div>

              
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
                <?php while ($pkg = $res->fetch_assoc()):
                    $img = $pkg['image_path'] ? YOGA_URL . $pkg['image_path'] : BASE_URL . "images/default-package.jpg";
                ?>
                  <div class="col-12">
                    <div class="card border-0 shadow-sm mb-3 retreat-list-item">
                      <div class="row g-0 align-items-stretch">
                        <!-- Left Image -->
                        <div class="col-md-4 col-lg-3">
                          <img src="<?= $img ?>" 
                              alt="<?= htmlspecialchars($pkg['package_title']) ?>" 
                              class="img-fluid h-100 w-100 rounded-start" 
                              style="object-fit: cover; aspect-ratio: 1 / 1;">
                        </div>

                        <!-- Right Details -->
                        <div class="col-md-8 col-lg-9">
                          <a href="packageDetails.php?id=<?= $pkg['id'] ?>">
                            <div class="card-body d-flex flex-column h-100">
                              <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($pkg['package_title']) ?></h5>
                                <div class="text-danger fw-bold fs-5">
                                  ₹<?= number_format($pkg['price_per_person'], 2) ?>
                                  <small class="text-muted fs-6">/person</small>
                                </div>
                              </div>
                              <div class="small text-muted mb-2">
                                <?= htmlspecialchars($pkg['retreat_title']) ?> • <?= htmlspecialchars($pkg['country']) ?> • <?= (int)$pkg['nights'] ?> nights
                              </div>
                              <p class="card-text text-secondary mb-3" style="max-width:95%;">
                                <?= htmlspecialchars(substr(strip_tags($pkg['description']), 0, 140)) ?>...
                              </p>
                            </div>
                          </a>
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
    <!-- FILTER MODAL (for mobile) -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="filterModalLabel">Refine Search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Duplicate the same filter form from sidebar -->
        <form id="filterFormModal" method="get" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>">
          <div class="mb-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, location...">
          </div>

          <div class="mb-3">
            <label class="form-label">Location</label>
            <?php
            $locs = $conn->query("SELECT DISTINCT country FROM organizations WHERE country<>'' ORDER BY country");
            if ($locs && $locs->num_rows > 0):
                while ($l = $locs->fetch_assoc()):
                    $val = $l['country'];
                    $checked = in_array($val, $locations) ? 'checked' : '';
            ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="location[]" id="mloc_<?= md5($val) ?>" value="<?= htmlspecialchars($val) ?>" <?= $checked ?>>
                  <label class="form-check-label small" for="mloc_<?= md5($val) ?>"><?= htmlspecialchars($val) ?></label>
                </div>
            <?php endwhile; endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Duration (nights)</label>
            <?php
            $dres = $conn->query("SELECT DISTINCT nights FROM yoga_packages WHERE nights > 0 ORDER BY nights ASC");
            if ($dres && $dres->num_rows > 0):
                while ($d = $dres->fetch_assoc()):
                    $dv = (int)$d['nights'];
                    $checked = in_array((string)$dv, $durations) ? 'checked' : '';
            ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="duration[]" id="mdur_<?= $dv ?>" value="<?= $dv ?>" <?= $checked ?>>
                  <label class="form-check-label small" for="mdur_<?= $dv ?>"><?= $dv ?> nights</label>
                </div>
            <?php endwhile; endif; ?>
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
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <a href="<?= YOGA_URL ?>index.php" class="btn btn-link small mt-2">Reset Filters</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>
