<?php
$query = "SELECT * FROM about_info";
$result = $conn->query($query);
?>
<div class="about_main_info">
    <div class="container">
        <div class="row">
            <?php while($row = $result->fetch_assoc()) { ?>
            <div class="col-xl-6 col-md-6">
                <div class="single_about_info">
                    <h3><?php echo htmlspecialchars($row['info_title']); ?></h3>
                    <?php echo $row['info_description']; ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
