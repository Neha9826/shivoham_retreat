<?php
// roomSlider.php
include 'db.php';

$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

// Fetch room images
$sql = "SELECT image_path FROM room_images WHERE room_id = $roomId";
$result = mysqli_query($conn, $sql);
?>

<div class="slider_area">
    <div class="slider_active owl-carousel">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): 
                $imgPath = $row['image_path'];
                // Add 'admin/' prefix if stored as 'uploads/...'
                if ($imgPath && str_starts_with($imgPath, 'uploads/')) {
                    $imageSrc = 'admin/' . $imgPath;
                } else {
                    $imageSrc = $imgPath;
                }
            ?>
                <div class="single_slider d-flex align-items-center justify-content-center" style="background-image: url('<?php echo htmlspecialchars($imageSrc); ?>'); background-size: cover; background-position: center;">
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="slider_text text-center">
                                    <h3>Shivoham Retreat</h3>
                                    <p>Unlock to enjoy the view of Mussoorie</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="single_slider d-flex align-items-center justify-content-center slider_bg_1">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="slider_text text-center">
                                <h3>No Images</h3>
                                <p>Room images not found.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
