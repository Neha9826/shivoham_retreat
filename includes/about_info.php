<?php
$query = "SELECT * FROM about_info";
$result = $conn->query($query);
?>
<div class="about_main_info">
    <div class="container">
        <div class="row">
            <?php 
            $count = 0;
            while($row = $result->fetch_assoc()) { 
                $count++;
                if ($count <= 2) {
                    // First two full width
                    echo '<div class="col-12">
                            <div class="single_about_info">
                                <h3>' . htmlspecialchars($row['info_title']) . '</h3>
                                ' . display_editor_content($row['info_description']) . '
                            </div>
                          </div>';
                } else {
                    // Rest in 2 columns
                    echo '<div class="col-md-6">
                            <div class="single_about_info">
                                <h3>' . htmlspecialchars($row['info_title']) . '</h3>
                                ' . display_editor_content($row['info_description']) . '
                            </div>
                          </div>';
                }
            }
            ?>
        </div>
    </div>
</div>
