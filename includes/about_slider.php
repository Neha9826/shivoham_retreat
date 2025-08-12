<?php
$query = "SELECT * FROM about_slider";
$result = $conn->query($query);
?>
<div class="container">
    <div class="owl-carousel small-slider">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="slider-item">
                <img src="<?php echo htmlspecialchars(build_image_url($row['image'])); ?>" alt="">
            </div>
        <?php endwhile; ?>
    </div>
</div>
