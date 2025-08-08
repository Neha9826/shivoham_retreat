<?php include 'session.php'; ?>
<?php include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload paths
    $mainDir = 'uploads/about/';
    $sliderDir = 'uploads/about_slider/';

    if (!is_dir($mainDir)) mkdir($mainDir, 0777, true);
    if (!is_dir($sliderDir)) mkdir($sliderDir, 0777, true);

    $title = mysqli_real_escape_string($conn, $_POST['main_heading']);
    $description = mysqli_real_escape_string($conn, $_POST['main_description']);

    // Image uploads
    $img1 = ''; $img2 = '';
    if (!empty($_FILES['main_image1']['name'])) {
        $img1 = $mainDir . time() . '_' . basename($_FILES['main_image1']['name']);
        move_uploaded_file($_FILES['main_image1']['tmp_name'], $img1);
    }
    if (!empty($_FILES['main_image2']['name'])) {
        $img2 = $mainDir . time() . '_' . basename($_FILES['main_image2']['name']);
        move_uploaded_file($_FILES['main_image2']['tmp_name'], $img2);
    }

    // Insert into about_1 table
    $query1 = "INSERT INTO about_1 (title, description, image_1, image_2, created_by) 
               VALUES ('$title', '$description', '$img1', '$img2', 1)";
    mysqli_query($conn, $query1);
    $about_id = mysqli_insert_id($conn);

    // Insert about_info blocks
    foreach ($_POST['info_title'] as $index => $info_title) {
        $info_desc = mysqli_real_escape_string($conn, $_POST['info_description'][$index]);
        mysqli_query($conn, "INSERT INTO about_info (title, description, created_by) 
                             VALUES ('$info_title', '$info_desc', 1)");
    }

    // Slider images
    foreach ($_FILES['slider_images']['tmp_name'] as $key => $tmp) {
        if (!empty($tmp)) {
            $name = time() . '_' . basename($_FILES['slider_images']['name'][$key]);
            $target = $sliderDir . $name;
            if (move_uploaded_file($tmp, $target)) {
                mysqli_query($conn, "INSERT INTO about_slider (image_path, created_by) 
                                     VALUES ('$target', 1)");
            }
        }
    }

    header("Location: allAbout.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<!-- Load CKEditor instead of TinyMCE -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>Add About Page Content</h2>
            <form method="POST" enctype="multipart/form-data">
                <!-- About Main Section -->
                <div class="card mb-4">
                    <div class="card-header">About Main Section</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Main Heading</label>
                            <input type="text" name="main_heading" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Main Description</label>
                            <textarea name="main_description" class="form-control editor" rows="5"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Image 1</label>
                            <input type="file" name="main_image1" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Image 2</label>
                            <input type="file" name="main_image2" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- About Info Section -->
                <div class="card mb-4">
                    <div class="card-header">About Info Section</div>
                    <div class="card-body" id="infoContainer">
                        <div class="info-item border p-3 mb-3">
                            <div class="mb-2">
                                <label>Title</label>
                                <input type="text" name="info_title[]" class="form-control" required>
                            </div>
                            <div>
                                <label>Description</label>
                                <!-- <textarea name="info_description[]" class="form-control editor" rows="3"></textarea> -->
                                <textarea name="info_description[]" class="form-control editor" id="editor0" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" id="addMoreInfo" class="btn btn-outline-primary">+ Add More</button>
                    </div>
                </div>

                <!-- About Slider Section -->
                <div class="card mb-4">
                    <div class="card-header">Slider Images</div>
                    <div class="card-body">
                        <label>Upload Images</label>
                        <input type="file" name="slider_images[]" class="form-control" multiple>
                        <small class="text-muted">You can select multiple images.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save About</button>
                <a href="allAbout.php" class="btn btn-secondary">Cancel</a>
            </form>
        </main>
    </div>
</div>

<script>
    // Initialize CKEditor for default fields
    CKEDITOR.replace('main_description');
    CKEDITOR.replace('editor0');

    let infoIndex = 1; // Starting index for new editors

    document.getElementById('addMoreInfo').addEventListener('click', () => {
        const container = document.getElementById('infoContainer');
        const editorId = `editor${infoIndex}`;

        const newGroup = document.createElement('div');
        newGroup.className = 'border p-3 mb-3 info-group';
        newGroup.innerHTML = `
            <div class="form-group mb-2">
                <label>Title:</label>
                <input type="text" name="info_title[]" class="form-control" required>
            </div>
            <div class="form-group mb-2">
                <label>Description:</label>
                <textarea name="info_description[]" id="${editorId}" class="form-control" required></textarea>
            </div>
            <button type="button" class="btn btn-danger btn-sm removeInfo">Remove</button>
        `;

        container.appendChild(newGroup);
        CKEDITOR.replace(editorId);
        infoIndex++;
    });

    // Handle removal of a dynamic section
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('removeInfo')) {
            const group = e.target.closest('.info-group');
            const textarea = group.querySelector('textarea');
            if (textarea && CKEDITOR.instances[textarea.id]) {
                CKEDITOR.instances[textarea.id].destroy();
            }
            group.remove();
        }
    });
</script>

<?php include 'includes/script.php'; ?>
<!-- <script src="https://cdn.ckeditor.com/4.25.1/standard-all/ckeditor.js"></script> -->
</body>
</html>
