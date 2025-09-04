<?php
$query = "SELECT * FROM about_slider";
$result = $conn->query($query);
?>
<div class="container">
    <div class="owl-carousel small-slider">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="slider-item">
                <img src="admin/<?= htmlspecialchars($row['image']) ?>" alt="">
            </div>
        <?php endwhile; ?>
    </div>
</div>