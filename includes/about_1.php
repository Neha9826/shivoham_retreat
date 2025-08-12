<?php
$query = "SELECT * FROM about_1 LIMIT 1";
$result = $conn->query($query);
$about = $result->fetch_assoc();
?>
<div class="about_area">
    <div class="container">
        <div class="row">
            <div class="col-xl-5 col-lg-5">
                <div class="about_info">
                    <div class="section_title mb-20px">
                        <span>About Us</span>
                        <h3><?php echo htmlspecialchars($about['main_heading']); ?></h3>
                    </div>
                    <?php echo $about['main_description']; ?>
                    <!-- <a href="#" class="line-button">Learn More</a> -->
                </div>
            </div>
            <div class="col-xl-7 col-lg-7">
                <div class="about_thumb d-flex">
                    <div class="img_1">
                        <img src="<?php echo htmlspecialchars(build_image_url($about['main_image1'])); ?>" alt="">
                    </div>
                    <div class="img_2">
                        <img src="<?php echo htmlspecialchars(build_image_url($about['main_image2'])); ?>" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
