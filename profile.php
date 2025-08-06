<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Booking history
$bookings = [];
$bookingStmt = $conn->prepare("SELECT * FROM bookings WHERE email = ? ORDER BY id DESC");
$bookingStmt->bind_param("s", $user['email']);
$bookingStmt->execute();
$result = $bookingStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$bookingStmt->close();

// Profile Image (future support)
$profileImage = (!empty($user['profile_image']) && file_exists($user['profile_image']))
    ? $user['profile_image']
    : 'assets/img/default-user.png';
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <title>My Profile</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ddd;
        }
        @media (max-width: 767px) {
            .profile-column, .booking-column {
                margin-top: 30px;
            }
        }

        .modal-backdrop.show {
  opacity: 0.5;
  z-index: 1040;
}
.modal.fade.show {
  z-index: 1050;
}

    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="bradcam_area breadcam_bg_1">
    <h3 class="mb-4 text-center">Welcome, <?= htmlspecialchars($user['name']) ?></h3>
</div>

<div class="container my-5">
    <div class="row">
        <?php if (!empty($_SESSION['profile_updated'])): ?>
  <div class="alert alert-success">Profile updated successfully.</div>
  <?php unset($_SESSION['profile_updated']); ?>
<?php endif; ?>

        <!-- ✅ User Profile Info -->
        <div class="col-lg-4 profile-column">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <!-- <img src="<?= $profileImage ?>" class="profile-img mb-3" alt="Profile Image"> -->
                    <?php
$profileImg = !empty($user['profile_image']) && file_exists($user['profile_image'])
    ? $user['profile_image']
    : 'assets/img/default-profile.png'; // fallback image
?>
<img src="<?= $profileImg ?>" alt="Profile Picture" class="rounded-circle" width="80" height="80" style="object-fit:cover;">

                    <h5 class="card-title"><?= htmlspecialchars($user['name']) ?></h5>
                    <table class="table table-sm table-bordered text-start mt-3">
                        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                        <tr><th>Phone</th><td><?= htmlspecialchars($user['phone']) ?></td></tr>
                        <tr><th>Member Since</th><td><?= date('d M Y', strtotime($user['created_at'])) ?></td></tr>
                    </table>
                    <!-- Trigger Modal -->
<button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
    Edit Profile
</button>

                </div>
            </div>
        </div>

        <!-- ✅ Booking History -->
        <div class="col-lg-8 booking-column">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3">Booking History</h5>
                    <?php if (empty($bookings)): ?>
                        <div class="alert alert-info">No bookings found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Guests</th>
                                        <th>Total (₹)</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td><?= $b['id'] ?></td>
                                            <td><?= date('d M Y', strtotime($b['check_in'])) ?></td>
                                            <td><?= date('d M Y', strtotime($b['check_out'])) ?></td>
                                            <td><?= $b['guests'] + $b['children'] ?></td>
                                            <td><?= $b['total_price'] ?></td>
                                            <td><span class="badge bg-<?= $b['status'] === 'booked' ? 'success' : ($b['status'] === 'cancelled' ? 'danger' : 'secondary') ?>">
                                                <?= ucfirst($b['status']) ?>
                                            </span></td>
                                            <td>
                                                <a href="viewBooking.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Edit Profile Modal -->
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="POST" action="editUser.php">
      <div class="modal-header">
        <h5 class="modal-title">Edit Your Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Profile Image -->
        <div class="form-group mb-3">
          <label for="editImage">Profile Image</label>
          <input type="file" id="editImage" accept="image/*" class="form-control">
          <img id="previewImage" src="<?= $profileImg ?>" class="img-fluid mt-2" style="max-height:200px;">
          <input type="hidden" name="cropped_image" id="croppedImage">
        </div>

        <!-- Name -->
        <div class="form-group mb-3">
          <label for="editName">Name</label>
          <input type="text" name="name" id="editName" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>

        <!-- Phone -->
        <div class="form-group mb-3">
          <label for="editPhone">Phone</label>
          <input type="text" name="phone" id="editPhone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>

        <!-- Password -->
        <div class="form-group mb-3">
          <label for="editPassword">New Password (optional)</label>
          <input type="password" name="password" id="editPassword" class="form-control">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
let cropper;
const editImage = document.getElementById('editImage');
const previewImage = document.getElementById('previewImage');
const croppedField = document.getElementById('croppedImage');

editImage.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (event) {
        previewImage.src = event.target.result;
        if (cropper) cropper.destroy();

        cropper = new Cropper(previewImage, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            cropend() {
                const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
                croppedField.value = canvas.toDataURL("image/png");
            }
        });
    };
    reader.readAsDataURL(file);
});

const form = document.querySelector('#editProfileModal form');
form.addEventListener('submit', function(e) {
    if (cropper) {
        const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
        const base64 = canvas.toDataURL("image/png");
        document.getElementById('croppedImage').value = base64;
    }
});
</script>
</body>
</html>
