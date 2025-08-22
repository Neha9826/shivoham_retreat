<?php
// Start session and include db connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Fetch contact info
$contact = [
    'address'   => '',
    'phone'     => '',
    'email'     => '',
    'map_embed' => ''
];
// ✅ FIX: Check for a valid database connection before querying
if (isset($conn) && !$conn->connect_error) {
    $result = $conn->query("SELECT * FROM contact_info LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $contact = $result->fetch_assoc();
    }
}


// Build quick-action links
$addrForMaps = urlencode($contact['address'] ?? '');
$directions  = $addrForMaps
    ? "http://maps.google.com/?q={$addrForMaps}"
    : "#";

// Make sure plainPhone exists before usage
$plainPhone  = !empty($contact['phone']) ? preg_replace('/\D+/', '', $contact['phone']) : '';
$telHref     = $plainPhone ? "tel:{$plainPhone}" : "#";
$waHref      = $plainPhone ? "https://wa.me/{$plainPhone}" : "#";
?>

<!doctype html>
<html class="no-js" lang="zxx">
<?php include 'includes/head.php'; ?>
<style>
    .map-hero { position: relative; width: 100%; }
    .map-hero iframe, .map-hero .map-frame { width: 100%; height: 480px; display: block; border: 0; }
    .map-overlay-card {
        position: absolute; right: 2rem; top: 2rem; width: 360px; max-width: calc(100% - 2rem);
        background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,.12);
    }
    .map-overlay-card h5 { margin: 0 0 .5rem; font-weight: 700; }
    .map-overlay-card .meta { font-size: .95rem; margin-bottom: .25rem; }
    .map-overlay-card .meta i { margin-right: .4rem; }
    .map-overlay-card .btn { width: 100%; margin-top: .5rem; }
    @media (max-width: 992px) {
        .map-overlay-card { position: static; width: 100%; margin: 12px auto 0; }
    }
</style>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/fixed_social_bar.php'; ?>
    <div class="bradcam_area breadcam_bg_1">
        <h3>Contact Us</h3>
    </div>
    <section class="contact-section">
        <div class="container">
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success" role="alert">
        ✅ Your message has been sent successfully! We’ll get back to you soon.
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        ❌ <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>


            <div class="map-hero">
                <?php
                // Map (URL turns into iframe; raw iframe is printed as-is)
                if (!empty($contact['map_embed'])) {
                    $mapCode = trim($contact['map_embed']);
                    if (preg_match('#^https?://#', $mapCode)) {
                        $encodedUrl = htmlspecialchars($mapCode);
                        echo "<iframe src=\"{$encodedUrl}\" loading=\"lazy\" allowfullscreen class=\"map-frame\"></iframe>";
                    } else {
                        // Assume raw iframe
                        echo $mapCode;
                    }
                } else {
                    echo '<iframe src="http://maps.google.com/maps?q=CMTC%20House,%20Kuthalwali,%20Johrigaon%20Dehradur,%20Uttarakhand-248003&output=embed" loading="lazy" allowfullscreen class="map-frame"></iframe>';
                }
                ?>

                <div class="map-overlay-card">
                    <h5>Visit Us</h5>

                    <?php if (!empty($contact['address'])): ?>
                    <div class="meta"><i class="ti-home"></i><?= nl2br(htmlspecialchars($contact['address'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($contact['phone'])): ?>
                    <div class="meta"><i class="ti-tablet"></i><?= htmlspecialchars($contact['phone']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($contact['email'])): ?>
                    <div class="meta"><i class="ti-email"></i><?= htmlspecialchars($contact['email']) ?></div>
                    <?php endif; ?>

                    <a href="<?= htmlspecialchars($directions) ?>" target="_blank" class="btn btn-primary btn-sm">Get Directions</a>
                    <?php if ($plainPhone): ?>
                    <a href="<?= htmlspecialchars($telHref) ?>" class="btn btn-outline-secondary btn-sm">Call Now</a>
                    <a href="<?= htmlspecialchars($waHref) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">WhatsApp</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <h2 class="contact-title">Get in Touch</h2>
                </div>
                <div class="col-lg-8">
                    <form class="form-contact contact_form" action="contact_process.php" method="post" id="contactForm">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <textarea class="form-control w-100" name="message" id="message" cols="30" rows="9"
                                        placeholder="Enter Message" required></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <input class="form-control" name="name" id="name" type="text" placeholder="Enter your name" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <input class="form-control" name="email" id="email" type="email" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input class="form-control" name="phone" id="phone" type="text" placeholder="Phone Number" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="button button-contactForm boxed-btn">Send</button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="media contact-info">
                        <span class="contact-info__icon"><i class="ti-home"></i></span>
                        <div class="media-body">
                            <h3>Address</h3>
                            <p><?= nl2br(htmlspecialchars($contact['address'])) ?></p>
                        </div>
                    </div>
                    <div class="media contact-info">
                        <span class="contact-info__icon"><i class="ti-tablet"></i></span>
                        <div class="media-body">
                            <h3><?= htmlspecialchars($contact['phone']) ?></h3>
                            <p>Mon to Fri 9am to 6pm</p>
                        </div>
                    </div>
                    <div class="media contact-info">
                        <span class="contact-info__icon"><i class="ti-email"></i></span>
                        <div class="media-body">
                            <h3><?= htmlspecialchars($contact['email']) ?></h3>
                            <p>Send us your query anytime!</p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/form.php'; ?>
    <?php include 'includes/js.php'; ?>
    <script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault(); // stop normal submit

    let form = this;
    let formData = new FormData(form);

    fetch('contact_process.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        let alertBox = document.createElement('div');
        alertBox.className = data.success ? 'alert alert-success' : 'alert alert-danger';
        alertBox.textContent = data.message;
        form.prepend(alertBox);

        if (data.success) {
            form.reset();
        }
    })
    .catch(err => {
        alert("Something went wrong! Please try again.");
        console.error(err);
    });
});
</script>

</body>
</html>