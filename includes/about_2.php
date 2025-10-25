<?php
// include 'admin/db.php';
$about2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM about_2 ORDER BY id DESC LIMIT 1"));
?>
<div class="about_area">
    <div class="container">
        <div class="row">
            <div class="col-xl-7 col-lg-7">
                <div class="about_thumb2 d-flex">
                    <div class="img_1">
                        <img src="admin/<?= htmlspecialchars($about2['image1']) ?>" alt="">
                    </div>
                    <div class="img_2">
                        <img src="admin/<?= htmlspecialchars($about2['image2']) ?>" alt="">
                    </div>
                </div>
            </div>
            <div class="col-xl-5 col-lg-5">
                <div class="about_info">
                    <div class="section_title mb-20px">
                        <span><?= htmlspecialchars($about2['title']) ?></span>
                        <h3><?= nl2br(htmlspecialchars($about2['heading'])) ?></h3>
                    </div>
                    <p><?php 
                        // The content from the text editor is already in HTML format.
                        // We need to remove any extra backslashes before displaying it.
                        $description = stripslashes($about2['description']);
                        // Now, echo the cleaned HTML directly.
                        echo $description; 
                        ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
