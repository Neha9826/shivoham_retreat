<?php
// packageDetails.php - robust, schema-aware, complete page

require_once __DIR__ . '/yoga_session.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php'; // provides $conn (mysqli)

// small helper (safe output)
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }


// package id
$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($packageId <= 0) {
    http_response_code(400);
    echo "Invalid package ID.";
    exit;
}

/*
  1) Fetch package + retreat + organization information
     (ensuring both package and retreat are published).
*/
$pkg = null;
$sql = "
    SELECT p.*, r.title AS retreat_title, r.short_description AS retreat_short, r.full_description AS retreat_full,
           r.style AS retreat_style, r.organization_id, o.name AS org_name, o.address, o.city, o.state, o.country
    FROM yoga_packages p
    JOIN yoga_retreats r ON p.retreat_id = r.id
    JOIN organizations o ON r.organization_id = o.id
    WHERE p.id = ? AND p.is_published = 1 AND r.is_published = 1
    LIMIT 1
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $packageId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $pkg = $res->fetch_assoc();
    }
    $stmt->close();
}
if (!$pkg) {
    http_response_code(404);
    echo "Package not found or not published.";
    exit;
}

$retreatId = (int)$pkg['retreat_id'];
$orgId = (int)$pkg['organization_id'];

/*
  2) Gallery: images (yoga_retreat_images) — fallback to yoga_retreat_media images if needed
*/
$gallery = [];
if ($gstmt = $conn->prepare("SELECT id, image_path, alt_text, is_primary FROM yoga_retreat_images WHERE retreat_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC")) {
    $gstmt->bind_param('i', $retreatId);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    while ($row = $gres->fetch_assoc()) {
        $gallery[] = $row;
    }
    $gstmt->close();
}
if (empty($gallery)) {
    // fallback to yoga_retreat_media images if that table exists
    $mediaExists = $conn->query("SHOW TABLES LIKE 'yoga_retreat_media'")->num_rows > 0;
    if ($mediaExists) {
        if ($mstmt = $conn->prepare("SELECT id, media_path FROM yoga_retreat_media WHERE retreat_id = ? AND type = 'image' ORDER BY id ASC")) {
            $mstmt->bind_param('i', $retreatId);
            $mstmt->execute();
            $mres = $mstmt->get_result();
            while ($row = $mres->fetch_assoc()) {
                $gallery[] = ['image_path' => $row['media_path'], 'alt_text' => ''];
            }
            $mstmt->close();
        }
    }
}

/*
  3) Daily schedule - yoga_package_schedule (if exists)
*/
$schedule = [];
if ($conn->query("SHOW TABLES LIKE 'yoga_package_schedule'")->num_rows) {
    if ($sstmt = $conn->prepare("SELECT id, time, activity FROM yoga_package_schedule WHERE package_id = ? ORDER BY time ASC, id ASC")) {
        $sstmt->bind_param('i', $packageId);
        $sstmt->execute();
        $sres = $sstmt->get_result();
        while ($r = $sres->fetch_assoc()) $schedule[] = $r;
        $sstmt->close();
    }
}

/*
  4) Skill levels - yoga_retreat_levels (may contain enum 'Beginner','Intermediate','Advanced','All')
*/
$levels = [];
if ($conn->query("SHOW TABLES LIKE 'yoga_retreat_levels'")->num_rows) {
    if ($lstmt = $conn->prepare("SELECT level FROM yoga_retreat_levels WHERE retreat_id = ? ORDER BY level ASC")) {
        $lstmt->bind_param('i', $retreatId);
        $lstmt->execute();
        $lres = $lstmt->get_result();
        while ($r = $lres->fetch_assoc()) $levels[] = $r['level'];
        $lstmt->close();
    }
}

/*
  5) Amenities: yoga_retreat_amenities -> yoga_amenities (yoga_amenities might not exist in your dump;
     check and only fetch if it exists)
*/
$amenities = [];
$amenitiesTableExists = $conn->query("SHOW TABLES LIKE 'yoga_amenities'")->num_rows > 0;
if ($conn->query("SHOW TABLES LIKE 'yoga_retreat_amenities'")->num_rows && $amenitiesTableExists) {
    if ($astmt = $conn->prepare("
        SELECT a.id, a.name, COALESCE(a.icon_class, '') AS icon_class
        FROM yoga_retreat_amenities ra
        JOIN yoga_amenities a ON ra.amenity_id = a.id
        WHERE ra.retreat_id = ?
        ORDER BY a.name ASC
    ")) {
        $astmt->bind_param('i', $retreatId);
        $astmt->execute();
        $ares = $astmt->get_result();
        while ($r = $ares->fetch_assoc()) $amenities[] = $r;
        $astmt->close();
    }
} else {
    // if yoga_amenities missing, try to get amenity names stored directly in yoga_retreat_amenities (if any)
    // (rare, but safe)
    if ($conn->query("SHOW TABLES LIKE 'yoga_retreat_amenities'")->num_rows) {
        $tmpRes = $conn->query("SELECT amenity_id FROM yoga_retreat_amenities WHERE retreat_id = " . intval($retreatId));
        if ($tmpRes && $tmpRes->num_rows) {
            // show IDs as placeholders if names not available
            while ($r = $tmpRes->fetch_assoc()) $amenities[] = ['id' => $r['amenity_id'], 'name' => 'Amenity #' . $r['amenity_id'], 'icon_class' => ''];
        }
    }
}

/*
  6) Instructors for this retreat (via yoga_retreat_instructors -> yoga_instructors)
*/
$instructors = [];
if ($conn->query("SHOW TABLES LIKE 'yoga_retreat_instructors'")->num_rows && $conn->query("SHOW TABLES LIKE 'yoga_instructors'")->num_rows) {
    if ($istmt = $conn->prepare("
        SELECT i.id, i.name, i.bio, i.photo, i.specialization, i.experience_years
        FROM yoga_retreat_instructors ri
        JOIN yoga_instructors i ON ri.instructor_id = i.id
        WHERE ri.retreat_id = ?
        ORDER BY i.name ASC
    ")) {
        $istmt->bind_param('i', $retreatId);
        $istmt->execute();
        $ires = $istmt->get_result();
        while ($r = $ires->fetch_assoc()) $instructors[] = $r;
        $istmt->close();
    }
}

/*
  7) Retreat media (videos) - optional
*/
$videos = [];
if ($conn->query("SHOW TABLES LIKE 'yoga_retreat_media'")->num_rows) {
    if ($mv = $conn->prepare("SELECT id, media_path, type FROM yoga_retreat_media WHERE retreat_id = ? AND type = 'video' ORDER BY id ASC")) {
        $mv->bind_param('i', $retreatId);
        $mv->execute();
        $mres = $mv->get_result();
        while ($r = $mres->fetch_assoc()) $videos[] = $r;
        $mv->close();
    }
}

/*
  8) Additional meta / accommodation / meals fields:
     Based on your SQL, yoga_packages contains nights, meals_included, price_per_person, min_persons, max_persons.
     The retreat may have 'style' and descriptions.
     Organization contains address, city, state, country.
*/

// --- fetch accommodations for this package (and their images)
$accommodations = [];
$acQ = $conn->prepare("SELECT id, accommodation_type, price_per_person FROM yoga_package_accommodations WHERE package_id = ? ORDER BY id ASC");
if ($acQ) {
    $acQ->bind_param('i', $packageId);
    $acQ->execute();
    $acRes = $acQ->get_result();
    while ($a = $acRes->fetch_assoc()) {
        $a['images'] = [];
        $imgQ = $conn->prepare("SELECT id, image_path FROM yoga_accommodation_images WHERE accommodation_id = ? ORDER BY id ASC");
        if ($imgQ) {
            $imgQ->bind_param('i', $a['id']);
            $imgQ->execute();
            $imgR = $imgQ->get_result();
            while ($im = $imgR->fetch_assoc()) $a['images'][] = $im;
            $imgQ->close();
        }
        $accommodations[] = $a;
    }
    $acQ->close();
}

// --- fetch batches for this package (open ones only)
$batches = [];
$bstmt = $conn->prepare("
    SELECT id, start_date, end_date, status, capacity, available_slots 
    FROM yoga_batches 
    WHERE package_id = ? AND status = 'open' 
    ORDER BY start_date ASC
");
if ($bstmt) {
    $bstmt->bind_param('i', $packageId);
    $bstmt->execute();
    $bres = $bstmt->get_result();
    while ($b = $bres->fetch_assoc()) {
        $batches[] = $b;
    }
    $bstmt->close();
}


// fallback hero image
$heroImage = !empty($gallery) ? ($gallery[0]['image_path'] ?? $gallery[0]) : ( $pkg['image'] ?? (BASE_URL . 'images/default-package.jpg') );

// reviews count (if y_reviews exists) - some sites use y_reviews table; your SQL has y_reviews -- check
$reviews_count = 0;
if ($conn->query("SHOW TABLES LIKE 'y_reviews'")->num_rows) {
    $rvq = $conn->prepare("SELECT COUNT(*) AS c FROM y_reviews WHERE retreat_id = ?");
    if ($rvq) {
        $rvq->bind_param('i', $retreatId);
        $rvq->execute();
        $rr = $rvq->get_result()->fetch_assoc();
        $reviews_count = (int)($rr['c'] ?? 0);
        $rvq->close();
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= esc($pkg['title']) ?></title>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <!-- Bootstrap (you can replace with your own CSS) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Simple icons (optional) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>yoga/yoga.css">
  <style>
    :root{
      --accent: #f4c430; /* golden accent */
      --muted: #6c757d;
      --content-max: 920px;
    }
    body {font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#222;}
    .hero {
      height: 56vh;
      min-height: 360px;
      display:flex;
      align-items:flex-end;
      background-size:cover;
      background-position:center;
      position:relative;
      color:#fff;
    }
    .hero .overlay{background:linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.6) 100%); padding:2.5rem; width:100%;}
    .hero .title {font-size: clamp(1.5rem, 3.6vw, 2.25rem); font-weight:700; margin-bottom:.25rem;}
    .badge-duration{background:var(--accent); color:#111; font-weight:600;}
    .gallery-thumbs img{cursor:pointer; border:2px solid transparent;}
    .gallery-thumbs img.active{border-color:var(--accent);}

    /* sticky booking box */
    .booking-box{position:sticky; top:20px;}
    .price-big{font-size:1.6rem; font-weight:700; color:#111;}
    /* content layout */
    .content-max{max-width:var(--content-max); margin:0 auto;}
    .section-title{font-size:1.15rem; font-weight:600; margin-bottom:.5rem;}
    .amenity {display:flex; gap:.5rem; align-items:center; padding:.35rem 0;}
    .instructor-photo {width:96px; height:96px; object-fit:cover; border-radius:50%;}
    /* responsive */
    @media (max-width:767px){ .hero{height:40vh;} .booking-box{position:static;} }
  </style>
</head>
<body class="yoga-page">
<!-- main site header (kept, but yoga.css will override visuals) -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- optional social bar -->
    <?php include __DIR__ . '/../includes/fixed_social_bar.php'; ?>

<?php include __DIR__ . '/yoga_navbar.php'; ?>

<!-- HERO -->
<section class="hero" style="background-image: url('<?= esc($heroImage) ?>')">
  <div class="overlay">
    <div class="container content-max">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h1 class="mb-1"><?= esc($pkg['title']) ?></h1>
          <div class="text-muted"><?= esc($pkg['retreat_title']) ?></div>
          <div class="mt-2">
            <span class="badge bg-warning text-dark"><?= (int)$pkg['nights'] ?> nights</span>
            <span class="ms-2 text-white-50">• <?= esc($pkg['min_persons']) ?>–<?= esc($pkg['max_persons']) ?> pax</span>
          </div>
        </div>
        <div class="text-end text-white small">
          <div><?= $reviews_count ?> reviews</div>
          <div class="mt-2"><span class="badge bg-dark small">Yoga • Retreat</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN -->
<div class="container my-4 content-max">
  <div class="row gx-4">
    <!-- LEFT -->
    <div class="col-lg-8">

      <!-- gallery (first image + thumbs) -->
      <?php if (!empty($gallery)): ?>
        <div class="mb-3">
          <div class="ratio ratio-16x9 mb-2" id="mainGallery">
            <img id="mainGalleryImg" src="<?= esc($gallery[0]['image_path'] ?? $gallery[0]) ?>" alt="<?= esc($pkg['title']) ?>" style="width:100%;height:100%;object-fit:cover">
          </div>
          <div class="d-flex gap-2 gallery-thumbs mb-2" id="thumbs">
            <?php foreach ($gallery as $i => $img): ?>
              <?php $imgPath = is_array($img) ? ($img['image_path'] ?? '') : $img; ?>
              <img src="<?= esc($imgPath) ?>" data-src="<?= esc($imgPath) ?>" width="120" height="70" class="<?= $i === 0 ? 'active' : '' ?>" style="object-fit:cover; cursor:pointer;">
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- overview -->
      <section class="mb-4">
        <div class="section-title">Overview</div>
        <div class="lead"><?= nl2br(esc($pkg['retreat_short'] ?: $pkg['description'] ?: '')) ?></div>
      </section>

      <!-- program / full description -->
      <section class="mb-4">
        <div class="section-title">Program</div>
        <div><?= nl2br(esc($pkg['retreat_full'] ?: $pkg['description'] ?: 'Program details will be updated.')) ?></div>
      </section>

      <!-- skill levels -->
      <section class="mb-4">
        <div class="section-title">Skill level</div>
        <?php if (!empty($levels)): ?>
          <div><?= esc(implode(', ', $levels)) ?></div>
        <?php else: ?>
          <div class="text-muted">All levels</div>
        <?php endif; ?>
      </section>

      <!-- styles -->
      <section class="mb-4">
        <div class="section-title">Yoga style</div>
        <div><?= esc($pkg['retreat_style'] ?: 'General Yoga') ?></div>
      </section>

      <!-- DAILY SCHEDULE -->
      <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div class="section-title">Typical daily schedule</div>
        </div>
        <?php if (!empty($schedule)): ?>
          <ul class="list-unstyled">
            <?php foreach ($schedule as $s): ?>
              <li class="mb-1"><strong><?= esc(date('h:i A', strtotime($s['time']))) ?></strong> — <?= esc($s['activity']) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <ul>
            <li class="text-muted">No daily schedule available for this package.</li>
          </ul>
        <?php endif; ?>
      </section>

      <!-- Amenities / Facilities -->
      <section class="mb-4">
        <div class="section-title">Facilities</div>
        <div class="row">
          <?php if (!empty($amenities)): ?>
            <?php foreach ($amenities as $am): ?>
              <div class="col-6 col-md-4 amenity mb-2">
                <?php if (!empty($am['icon_class'])): ?><i class="bi <?= esc($am['icon_class']) ?> me-2"></i><?php endif; ?>
                <span><?= esc($am['name']) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-muted">No facilities listed yet.</div>
          <?php endif; ?>
        </div>
      </section>

      <!-- INSTRUCTORS -->
      <section class="mb-5">
        <h3 class="fw-bold mb-4">Meet the Instructors</h3>
        <?php if (!empty($instructors)): ?>
          <div id="instructorList">
            <?php foreach ($instructors as $index => $ins): ?>
              <div class="card border-0 shadow-sm mb-3 p-3 align-items-center flex-md-row instructor-card <?= $index >= 2 ? 'extra-instructor d-none' : '' ?>">
                <div class="text-center mb-3 mb-md-0" style="flex:0 0 110px;">
                  <img src="../<?= esc($ins['photo'] ?? BASE_URL . 'uploads/default-user.png') ?>"
                      alt="<?= esc($ins['name']) ?>"
                      class="rounded-circle instructor-photo"
                      style="width:90px; height:90px; object-fit:cover;">
                </div>
                <div class="ms-md-3 flex-grow-1">
                  <h6 class="fw-semibold mb-1"><?= esc($ins['name']) ?></h6>
                  <?php if (!empty($ins['specialization'])): ?>
                    <p class="text-muted small mb-1"><?= esc($ins['specialization']) ?></p>
                  <?php endif; ?>
                  <p class="text-secondary small lh-base mb-0" style="max-width:600px;">
                    <?= nl2br(esc(substr($ins['bio'], 0, 350))) ?>
                    <?= strlen($ins['bio']) > 350 ? '…' : '' ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (count($instructors) > 2): ?>
            <div class="text-center mt-3">
              <button type="button" id="toggleInstructors" class="btn btn-outline-secondary btn-sm">
                Show More <i class="bi bi-chevron-down"></i>
              </button>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <p class="text-muted">No instructors listed yet.</p>
        <?php endif; ?>
      </section>

      <script>
      document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('toggleInstructors');
        if (!toggleBtn) return;

        toggleBtn.addEventListener('click', () => {
          const hiddenCards = document.querySelectorAll('.extra-instructor');
          const isHidden = hiddenCards[0].classList.contains('d-none');
          hiddenCards.forEach(c => c.classList.toggle('d-none'));

          toggleBtn.innerHTML = isHidden
            ? 'Show Less <i class="bi bi-chevron-up"></i>'
            : 'Show More <i class="bi bi-chevron-down"></i>';
        });
      });
      </script>

      <!-- Location & Contact -->
      <section class="mb-5">
        <div class="section-title">Location & Contact</div>
          <?= esc($pkg['address'] ?? '') ?><br>
          <?= esc($pkg['city'] ?? '') ?><?= (!empty($pkg['state']) ? ', ' . esc($pkg['state']) : '') ?><?= (!empty($pkg['country']) ? ', ' . esc($pkg['country']) : '') ?>
      </section>    

      <!-- Videos -->
      <?php if (!empty($videos)): ?>
        <section class="mb-4">
          <div class="section-title">Videos</div>
          <div class="ratio ratio-16x9">
            <?php
            // show first video (assumes media_path is a URL or embed)
            $v = $videos[0];
            $mp = esc($v['media_path']);
            // naive: if it's youtube link, embed; otherwise show link
            if (strpos($mp, 'youtube.com') !== false || strpos($mp, 'youtu.be') !== false) {
                // try to extract id and embed
                $ytid = null;
                if (preg_match('#(?:v=|/)([A-Za-z0-9_-]{6,})#', $mp, $m)) $ytid = $m[1];
                if ($ytid) {
                    echo '<iframe src="https://www.youtube.com/embed/' . esc($ytid) . '" style="width:100%;height:100%;" frameborder="0" allowfullscreen></iframe>';
                } else {
                    echo '<a href="' . $mp . '" target="_blank">' . $mp . '</a>';
                }
            } else {
                echo '<a href="' . $mp . '" target="_blank">' . $mp . '</a>';
            }
            ?>
          </div>
        </section>
      <?php endif; ?>

    </div> <!-- /left -->

    <!-- RIGHT: booking box -->
    <div class="col-lg-4">
      <div class="share-box text-center">
        <h6 class="text-uppercase fw-bold mb-2">Share this retreat</h6>
        <div class="d-flex justify-content-center gap-3">
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . 'yoga/packageDetails.php?id=' . $pkg['id']) ?>"
            target="_blank" class="share-icon fb"><i class="bi bi-facebook"></i></a>
          <a href="https://api.whatsapp.com/send?text=<?= urlencode('Check out this Yoga Retreat: ' . BASE_URL . 'yoga/packageDetails.php?id=' . $pkg['id']) ?>"
            target="_blank" class="share-icon wa"><i class="bi bi-whatsapp"></i></a>
          <a href="https://www.instagram.com/" target="_blank" class="share-icon ig"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
      <hr class="my-3">

      <!-- Booking section -->
      <div class="booking-box border rounded p-3">
        <div class="mb-3">
          <div class="small text-muted">Starting from</div>
          <div class="text-muted small">Price per person</div>
          <div id="basePrice" class="price-big mb-3">₹ <?= number_format((float)$pkg['price_per_person'], 0) ?></div>
        </div>

        <!-- Dynamic date selection -->
        <!-- Batch calendar selection -->
<div class="mb-3">
  <label class="form-label small">Select Batch or Choose Custom Dates</label>

  <?php if (!empty($batches)): ?>
    <div id="batchCalendar" class="border rounded p-2 mb-2 bg-light">
      <?php foreach ($batches as $b): 
        $start = date('M d, Y', strtotime($b['start_date']));
        $end = date('M d, Y', strtotime($b['end_date']));
        $slots = (int)$b['available_slots'];
      ?>
        <div class="border rounded p-2 mb-2 batch-option" 
             data-id="<?= $b['id'] ?>"
             data-start="<?= $b['start_date'] ?>"
             data-end="<?= $b['end_date'] ?>">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong><?= $start ?> → <?= $end ?></strong>
              <div class="small text-muted">Available Slots: <?= $slots ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary selectBatchBtn">Select</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-muted small mb-2">No predefined batches. You can select your own dates below.</div>
  <?php endif; ?>

  <input type="hidden" name="batch_id" id="batch_id" value="">
</div>

<!-- Date inputs -->
<div class="mb-3">
  <label class="form-label small">Arrival date</label>
  <input type="date" id="check_in" class="form-control">
</div>
<div class="mb-3">
  <label class="form-label small">Checkout date</label>
  <input type="date" id="check_out" class="form-control" readonly>
  <input type="hidden" id="package_nights" value="<?= (int)$pkg['nights'] ?>">
</div>


        <!-- Accommodation selection -->
        <div class="mb-3">
          <label class="form-label small">Accommodation</label>
          <div class="list-group" id="accomList">
            <!-- Standard price option -->
            <label class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <input type="radio" name="accommodation_id" value="0" checked onchange="updateSelectedPrice()">
                <span class="fw-bold ms-2">Standard (No accommodation)</span>
                <div class="small text-muted ms-4">₹<?= number_format((float)$pkg['price_per_person'], 2) ?></div>
              </div>
            </label>

            <?php if (!empty($accommodations)): ?>
              <?php foreach ($accommodations as $idx => $acc): 
                $priceLabel = number_format((float)$acc['price_per_person'], 2);
                $imgsJson = htmlspecialchars(json_encode(array_column($acc['images'],'image_path')), ENT_QUOTES, 'UTF-8');
              ?>
                <label class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <input type="radio" name="accommodation_id" value="<?= (int)$acc['id'] ?>" onchange="updateSelectedPrice()">
                    <span class="fw-bold ms-2"><?= htmlspecialchars($acc['accommodation_type']) ?></span>
                    <div class="small text-muted ms-4">₹<?= $priceLabel ?></div>
                  </div>
                  <div>
                    <button type="button" class="btn btn-link btn-sm" onclick='openAccomModal(<?= json_encode((int)$acc["id"]) ?>, <?= $imgsJson ?>, <?= json_encode($acc["accommodation_type"]) ?>, <?= json_encode($priceLabel) ?>)'>
                      Show photos
                    </button>
                  </div>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Selected price display -->
        <div class="mb-3">
          <div class="small text-muted">Selected price</div>
          <div id="selectedPrice" class="fw-bold fs-5">₹<?= number_format((float)$pkg['price_per_person'], 2) ?> / person</div>
        </div>
      </div>

      <!-- Sticky button area -->
      <!-- Fixed Button Bar (always visible at bottom of viewport) -->
        <div class="fixed-booking-buttons">
          <div class="container px-3">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-info flex-fill" id="sendQueryBtn">Send query</button>
              <button type="button" class="btn btn-info flex-fill" id="requestBookBtn">Request to book</button>
            </div>
          </div>
        </div>
    </div>



    <!-- Accommodation Photos Modal -->
    <div class="modal fade" id="accomModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="accomModalTitle">Accommodation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="accomCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner" id="accomCarouselInner"></div>
              <button class="carousel-control-prev" type="button" data-bs-target="#accomCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#accomCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
              </button>
            </div>
            <div class="mt-3">
              <div id="accomModalPrice" class="fw-bold"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Query Modal -->
    <div class="modal fade" id="queryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="queryForm">
            <div class="modal-header">
              <h5 class="modal-title">Send query</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>">
              <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
              <div class="mb-2"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
              <div class="mb-2"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
              <div class="mb-2 d-flex gap-2 align-items-center">
                <div style="flex:1"><label class="form-label">Arrival date</label><input name="arrival_date" id="query_arrival_date" type="date" class="form-control"></div>
                <div class="form-check ms-2">
                  <input type="checkbox" id="query_no_date" name="no_dates_yet" value="1" class="form-check-input">
                  <label class="form-check-label small" for="query_no_date">No dates yet</label>
                </div>
              </div>
              <div class="mb-2"><label class="form-label">Message</label><textarea name="message" class="form-control"></textarea></div>
              <div id="queryStatus" class="small mt-2"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Send</button></div>
          </form>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="bookingForm">
            <div class="modal-header">
              <h5 class="modal-title">Request to Book</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>">
              <input type="hidden" name="retreat_id" value="<?= (int)$pkg['retreat_id'] ?>">
              <input type="hidden" name="batch_id" id="booking_batch_id">

              <div class="mb-2">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" value="<?= isset($_SESSION['yoga_user_name']) ? htmlspecialchars($_SESSION['yoga_user_name']) : '' ?>" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Email</label> 
                <input name="email" type="email" class="form-control" value="<?= isset($_SESSION['yoga_user_email']) ? htmlspecialchars($_SESSION['yoga_user_email']) : '' ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="<?= isset($_SESSION['yoga_user_phone']) ? htmlspecialchars($_SESSION['yoga_user_phone']) : '' ?>">
              </div>

              <div class="mb-2">
                <label class="form-label">Arrival date</label>
                <input name="arrival_date" id="booking_arrival_date" type="date" class="form-control">
              </div>

              <div class="mb-2">
                <label class="form-label">Number of Guests</label>
                <input name="guests" type="number" min="1" value="1" class="form-control">
              </div>

              <div class="mb-2">
                <label class="form-label">Accommodation</label>
                <select name="accommodation_id" class="form-select">
                  <option value="">Standard (no accommodation)</option>
                  <?php foreach ($accommodations as $acc): ?>
                    <option value="<?= (int)$acc['id'] ?>" data-price="<?= (float)$acc['price_per_person'] ?>">
                      <?= esc($acc['accommodation_type']) ?> — ₹<?= number_format((float)$acc['price_per_person'], 2) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control"></textarea>
              </div>

              <div id="bookingStatus" class="small mt-2 text-muted"></div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-warning">Request to Book</button>
            </div>
          </form>
        </div>
      </div>
    </div>


    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  // gallery thumbnail click behavior (simple)
  document.addEventListener('click', function (e) {
    const el = e.target;
    if (el && el.closest && el.closest('.gallery-thumbs img')) {
      const img = el.closest('img');
      document.querySelectorAll('.gallery-thumbs img').forEach(i => i.classList.remove('active'));
      img.classList.add('active');
      const main = document.getElementById('mainGalleryImg');
      if (main) main.src = img.dataset.src || img.src;
    }
  });

/* Helper: add days to date (returns yyyy-mm-dd) */
function addDaysToDate(inputDate, days) {
  const d = new Date(inputDate);
  d.setDate(d.getDate() + Number(days));
  return d.toISOString().split('T')[0];
}

// Auto set checkout when checkin selected
document.getElementById('check_in').addEventListener('change', function() {
  const nights = parseInt(document.getElementById('package_nights').value) || 0;
  if (!this.value) return;
  const co = addDaysToDate(this.value, nights);
  document.getElementById('check_out').value = co;
  document.getElementById('query_arrival_date').value = this.value;
  document.getElementById('booking_arrival_date').value = this.value;
});

function updateSelectedPrice() {
  const radios = document.querySelectorAll('input[name="accommodation_id"]');
  let selectedText = '';
  for (const r of radios) {
    if (r.checked) {
      const parent = r.closest('.list-group-item');
      const txt = parent.querySelector('.small.text-muted');
      selectedText = txt ? txt.textContent : '';
      break;
    }
  }
  document.getElementById('selectedPrice').textContent = selectedText || '₹<?= number_format((float)$pkg['price_per_person'],2) ?>';
}
updateSelectedPrice();

// --- Batch selection logic ---
document.querySelectorAll('.selectBatchBtn').forEach(btn => {
  btn.addEventListener('click', function() {
    // remove active highlight from all
    document.querySelectorAll('.batch-option').forEach(b => b.classList.remove('active'));
    
    const batchBox = this.closest('.batch-option');
    batchBox.classList.add('active');
    
    const batchId = batchBox.dataset.id;
    const start = batchBox.dataset.start;
    const end = batchBox.dataset.end;

    // set hidden input
    document.getElementById('batch_id').value = batchId;

    // auto fill dates
    const ci = document.getElementById('check_in');
    const co = document.getElementById('check_out');
    ci.value = start;
    co.value = end;

    ci.setAttribute('readonly', true);
    co.setAttribute('readonly', true);

    // visual feedback
    btn.textContent = 'Selected';
    btn.classList.replace('btn-outline-primary', 'btn-success');

    // reset other buttons
    document.querySelectorAll('.selectBatchBtn').forEach(other => {
      if (other !== btn) {
        other.textContent = 'Select';
        other.classList.remove('btn-success');
        other.classList.add('btn-outline-primary');
      }
    });
  });
});

// allow custom date selection
document.getElementById('check_in').addEventListener('focus', function() {
  document.getElementById('batch_id').value = '';
  document.querySelectorAll('.batch-option').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.selectBatchBtn').forEach(b => {
    b.textContent = 'Select';
    b.classList.remove('btn-success');
    b.classList.add('btn-outline-primary');
  });
  this.removeAttribute('readonly');
});


// Show Accommodation Photos
function openAccomModal(title, imgs, priceLabel) {
  const inner = document.getElementById('accomCarouselInner');
  document.getElementById('accomModalTitle').textContent = title;
  inner.innerHTML = '';

  if (!imgs || imgs.length === 0) {
    inner.innerHTML = '<div class="carousel-item active"><div class="p-4 text-center text-muted">No images available</div></div>';
  } else {
    imgs.forEach((src, i) => {
      inner.innerHTML += `
        <div class="carousel-item ${i === 0 ? 'active' : ''}">
          <img src="../${src}" class="d-block w-100" style="height:400px;object-fit:cover;">
        </div>`;
    });
  }
  document.getElementById('accomModalPrice').textContent = 'Price: ₹' + priceLabel + ' / person';
  new bootstrap.Modal(document.getElementById('accomModal')).show();
}

// Button handlers
document.getElementById('sendQueryBtn').addEventListener('click', () => {
  new bootstrap.Modal(document.getElementById('queryModal')).show();
});
document.getElementById('requestBookBtn').addEventListener('click', () => {
  // 1️⃣ Sync arrival date from main booking box
  const arrivalDate = document.getElementById('check_in').value || '';
  document.getElementById('booking_arrival_date').value = arrivalDate;

  // 2️⃣ Sync selected accommodation
  const selectedAccom = document.querySelector('input[name="accommodation_id"]:checked');
  const bookingAccomSelect = document.querySelector('#bookingForm select[name="accommodation_id"]');
  if (selectedAccom && bookingAccomSelect) {
    bookingAccomSelect.value = selectedAccom.value;
  }

  // 3️⃣ Finally open modal
  new bootstrap.Modal(document.getElementById('bookingModal')).show();
});


// AJAX submit (Query)
document.addEventListener("DOMContentLoaded", function() {
  const queryForm = document.getElementById("queryForm");
  if (!queryForm) return;

  queryForm.addEventListener("submit", async function(e) {
    e.preventDefault();
    const status = document.getElementById("queryStatus");
    status.textContent = "Sending...";

    try {
      // adjust path if packageDetails.php is in /yoga/
      const res = await fetch("submitQuery.php", {
        method: "POST",
        body: new FormData(e.target)
      });

      // try to parse JSON safely
      let j;
      try {
        j = await res.json();
      } catch (err) {
        const txt = await res.text();
        console.error("Response not JSON:", txt);
        status.textContent = "Server error — invalid response.";
        return;
      }

      if (j.success) {
        status.textContent = "Sent successfully!";
        e.target.reset();
        setTimeout(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("queryModal"));
          if (modal) modal.hide();
          status.textContent = "";
        }, 1200);
      } else {
        status.textContent = j.msg || "Error sending.";
      }

    } catch (err) {
      console.error("Fetch error:", err);
      status.textContent = "Network error — please try again.";
    }
  });
});

// AJAX submit Booking
document.getElementById('bookingForm').addEventListener('submit', async e => {
  e.preventDefault();

    // sync batch id from the main calendar
  const selectedBatch = document.getElementById('batch_id').value || '';
  document.getElementById('booking_batch_id').value = selectedBatch;

  const status = document.getElementById('bookingStatus');
  status.innerHTML = '<span class="text-muted">Sending booking request...</span>';

  try {
    const res = await fetch('submitBooking.php', { method: 'POST', body: new FormData(e.target) });
    const j = await res.json();

    if (j.require_login) {
  status.innerHTML = `
    <div class="alert alert-warning p-2 mt-2">
      ${j.msg}<br>
      <a href="<?= YOGA_URL ?>login.php" class="btn btn-sm btn-primary mt-2">Login to Continue</a>
    </div>
  `;
  return;
}


    if (j.success) {
      status.innerHTML = '<span class="text-success">Booking request sent successfully!</span>';
      setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
      }, 1200);
    } else {
      status.innerHTML = '<span class="text-danger">' + (j.msg || 'Error submitting booking.') + '</span>';
    }
  } catch (err) {
    status.innerHTML = '<span class="text-danger">Network error — please try again.</span>';
  }
});


</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
