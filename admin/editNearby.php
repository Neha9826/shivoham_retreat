<?php
include 'session.php';
include 'db.php';
include 'includes/helpers.php'; // Correct include path for the helper file

$placeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($placeId == 0) {
    header("Location: allNearby.php");
    exit;
}

$mainQuery = mysqli_query($conn, "SELECT * FROM nearby_places_main WHERE id = $placeId");
$mainData = mysqli_fetch_assoc($mainQuery);

if (!$mainData) {
    echo "Place not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<body>
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>Edit Nearby Place</h2>
            <div id="message-area" class="alert d-none"></div>

            <div class="card mb-4">
                <div class="card-header">Main Destination Details</div>
                <div class="card-body">
                    <form id="main-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="place_id" name="place_id" value="<?= $placeId ?>">
                        <div class="form-group mb-3">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($mainData['title']) ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="google_maps_link">Google Maps Link</label>
                            <input type="url" id="google_maps_link" name="google_maps_link" class="form-control" value="<?= htmlspecialchars($mainData['Maps_link']) ?>">
                        </div>
                        <div class="form-group mb-3">
                            <label for="main_image">Main Image</label>
                            <?php if ($mainData['main_image']): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars(resolve_admin_image_url($mainData['main_image'])) ?>" alt="Main Image" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="main_image" name="main_image" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary" id="save-main-btn">Update Main Details</button>
                    </form>
                </div>
            </div>

            <div id="sections-container">
                <div class="card mb-4">
                    <div class="card-header">Add Sections and Images</div>
                    <div class="card-body">
                        <form id="section-form" class="mb-4">
                            <input type="hidden" id="section_place_id" name="nearby_place_id" value="<?= $placeId ?>">
                            <input type="hidden" id="section_id" name="section_id">
                            <div class="form-group mb-3">
                                <label for="side_heading">Side Heading</label>
                                <input type="text" id="side_heading" name="side_heading" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" id="save-section-btn">Save Section</button>
                            <button type="button" class="btn btn-secondary" onclick="resetSectionForm()">Clear Section</button>
                        </form>

                        <div id="section-image-uploader" style="display:none;">
                            <h4>Upload Images for "<span id="section-heading-name"></span>"</h4>
                            <form id="image-form" enctype="multipart/form-data">
                                <input type="hidden" id="image_section_id" name="nearby_place_section_id">
                                <div class="form-group mb-3">
                                    <label for="images">Images</label>
                                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple required>
                                </div>
                                <button type="submit" class="btn btn-info">Upload Images</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Existing Sections</div>
                    <div class="card-body" id="existing-sections-list">
                        </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>
<?php include 'includes/script.php'; ?>
<script>
    CKEDITOR.replace('description');

    const mainForm = document.getElementById('main-form');
    const sectionsContainer = document.getElementById('sections-container');
    const sectionForm = document.getElementById('section-form');
    const imageUploader = document.getElementById('section-image-uploader');
    const messageArea = document.getElementById('message-area');
    const placeId = <?= $placeId ?>;

    function showMessage(msg, type) {
        messageArea.textContent = msg;
        messageArea.className = `alert alert-${type}`;
        messageArea.classList.remove('d-none');
    }

    function resetSectionForm() {
        sectionForm.reset();
        document.getElementById('section_id').value = '';
        document.getElementById('save-section-btn').innerText = 'Save Section';
        imageUploader.style.display = 'none';
        CKEDITOR.instances['description'].setData('');
    }

    function fetchSections(id) {
        fetch(`nearby_places/sections/get.php?place_id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const list = document.getElementById('existing-sections-list');
                list.innerHTML = '';
                if (data.success && data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        let imagesHtml = '<div class="row">';
                        section.images.forEach(image => {
                            // Use the full URL provided by the PHP script
                            imagesHtml += `<div class="col-sm-3 mb-2">
                                <img src="${image.image_path_full}" class="img-fluid" alt="${section.side_heading}">
                                <button type="button" class="btn btn-danger btn-sm w-100 mt-1 delete-image-btn" data-id="${image.id}">Delete</button>
                            </div>`;
                        });
                        imagesHtml += '</div>';

                        list.innerHTML += `
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5>${section.side_heading}</h5>
                                    <div>${section.description}</div>
                                    <p class="mt-2">
                                        <button type="button" class="btn btn-sm btn-warning edit-section-btn" data-id="${section.id}">Edit</button>
                                        <button type="button" class="btn btn-sm btn-danger delete-section-btn" data-id="${section.id}">Delete</button>
                                        <button type="button" class="btn btn-sm btn-info upload-images-btn" data-id="${section.id}" data-heading="${section.side_heading}">Upload Images</button>
                                    </p>
                                    <div class="image-previews mt-2">
                                        ${imagesHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else if (data.success && data.sections.length === 0) {
                    list.innerHTML = '<p>No sections found for this place. Add one above.</p>';
                } else {
                    showMessage(data.error || 'Error loading sections.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error fetching sections:', error);
                showMessage('Error loading sections.', 'danger');
            });
    }

    // Function to run on page load
    document.addEventListener('DOMContentLoaded', () => {
        if (placeId) {
            fetchSections(placeId);
        }
    });

    mainForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('nearby_places/main/insert.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                // The placeId is already set from the URL
                sectionsContainer.style.display = 'block';
                fetchSections(placeId);
            } else {
                showMessage(`Error: ${data.error}`, 'danger');
            }
        });
    });

    sectionForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('nearby_place_id', document.getElementById('section_place_id').value);
        formData.append('section_id', document.getElementById('section_id').value);
        formData.append('side_heading', document.getElementById('side_heading').value);
        formData.append('description', CKEDITOR.instances['description'].getData());

        fetch('nearby_places/sections/insert.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                fetchSections(placeId);
                resetSectionForm();
            } else {
                showMessage(`Error: ${data.error}`, 'danger');
            }
        });
    });

    document.getElementById('existing-sections-list').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-section-btn')) {
            const sectionId = e.target.dataset.id;
            fetch(`nearby_places/sections/get.php?section_id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const section = data.data;
                        document.getElementById('section_id').value = section.id;
                        document.getElementById('side_heading').value = section.side_heading;
                        CKEDITOR.instances['description'].setData(section.description);
                        document.getElementById('save-section-btn').innerText = 'Update Section';
                        imageUploader.style.display = 'block';
                        document.getElementById('image_section_id').value = section.id;
                        document.getElementById('section-heading-name').innerText = section.side_heading;
                        document.getElementById('sections-container').scrollIntoView({behavior: 'smooth'});
                    } else {
                        showMessage('Section not found.', 'danger');
                    }
                });
        }
        if (e.target.classList.contains('delete-section-btn')) {
            if (confirm('Are you sure you want to delete this section and all its images?')) {
                const sectionId = e.target.dataset.id;
                fetch(`nearby_places/sections/delete.php?id=${sectionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            fetchSections(placeId);
                        } else {
                            showMessage(`Error: ${data.error}`, 'danger');
                        }
                    });
            }
        }
        if (e.target.classList.contains('upload-images-btn')) {
            const sectionId = e.target.dataset.id;
            const heading = e.target.dataset.heading;
            document.getElementById('image_section_id').value = sectionId;
            document.getElementById('section-heading-name').innerText = heading;
            imageUploader.style.display = 'block';
            imageUploader.scrollIntoView({behavior: 'smooth'});
        }
        if (e.target.classList.contains('delete-image-btn')) {
            if (confirm('Are you sure you want to delete this image?')) {
                const imageId = e.target.dataset.id;
                fetch(`nearby_places/images/delete.php?id=${imageId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            fetchSections(placeId);
                        } else {
                            showMessage(`Error: ${data.error}`, 'danger');
                        }
                    });
            }
        }
    });

    document.getElementById('image-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('nearby_places/images/insert.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                fetchSections(placeId);
                document.getElementById('images').value = '';
            } else {
                showMessage(`Error: ${data.error}`, 'danger');
            }
        });
    });

</script>
</body>
</html>