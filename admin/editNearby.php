<?php
include 'session.php';
include 'db.php';
include 'includes/helpers.php';

$placeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($placeId == 0) { header("Location: allNearby.php"); exit; }

$mainQuery = $conn->prepare("SELECT * FROM nearby_places_main WHERE id = ?");
$mainQuery->bind_param("i", $placeId);
$mainQuery->execute();
$mainData = $mainQuery->get_result()->fetch_assoc();
$mainQuery->close();

if (!$mainData) { echo "Place not found."; exit; }
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
      <div id="message-area" class="alert d-none" role="alert"></div>

      <!-- Main -->
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
              <input type="url" id="google_maps_link" name="google_maps_link" class="form-control" value="<?= htmlspecialchars($mainData['Maps_link'] ?? '') ?>">
            </div>

            <div class="form-group mb-3">
              <label for="main_description">Description</label>
              <textarea id="main_description" name="description" class="form-control"><?= htmlspecialchars($mainData['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group mb-3">
              <label for="main_image">Main Image</label>
              <?php if (!empty($mainData['main_image'])): ?>
                <div class="mb-2">
                  <img src="<?= htmlspecialchars(build_image_url($mainData['main_image'])) ?>" alt="Main Image" style="max-width: 220px; border-radius:8px;">
                </div>
              <?php endif; ?>
              <input type="file" id="main_image" name="main_image" class="form-control" accept="image/*">
              <small class="text-muted">Leave blank to keep the current image.</small>
            </div>

            <button type="submit" class="btn btn-primary" id="save-main-btn">Update Main Details</button>
          </form>
        </div>
      </div>

      <!-- Sections + Images -->
      <div id="sections-container">
        <div class="card mb-4">
          <div class="card-header">Add / Edit Sections</div>
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
              <button type="button" class="btn btn-secondary" id="clear-section-btn">Clear</button>
            </form>

            <div id="section-image-uploader" style="display:none;">
              <h5>Upload Images for "<span id="section-heading-name"></span>"</h5>
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
          <div class="card-body" id="existing-sections-list"></div>
        </div>
      </div>
    </main>
    <?php include 'includes/footer.php'; ?>
  </div>
</div>

<?php include 'includes/script.php'; ?>
<script>
// Editors
CKEDITOR.replace('main_description');
CKEDITOR.replace('description');

const placeId = <?= $placeId ?>;
const messageArea = document.getElementById('message-area');

function showMessage(msg, type='info') {
  messageArea.textContent = msg;
  messageArea.className = 'alert alert-' + type;
  messageArea.classList.remove('d-none');
}

function resetSectionForm() {
  document.getElementById('section-form').reset();
  document.getElementById('section_id').value = '';
  document.getElementById('save-section-btn').innerText = 'Save Section';
  document.getElementById('section-image-uploader').style.display = 'none';
  if (CKEDITOR.instances['description']) CKEDITOR.instances['description'].setData('');
}

function fetchSections(id) {
  fetch('nearby_places/sections/get.php?place_id=' + encodeURIComponent(id))
    .then(r => r.json())
    .then(data => {
      const list = document.getElementById('existing-sections-list');
      list.innerHTML = '';

      if (data.success && Array.isArray(data.sections) && data.sections.length) {
        data.sections.forEach(section => {
          let imagesHtml = '<div class="row">';
          (section.images || []).forEach(image => {
            imagesHtml += `
              <div class="col-sm-3 mb-2">
                <img src="${image.image_path_full}" class="img-fluid" alt="${section.side_heading}">
                <div class="d-flex justify-content-between mt-1">
                  <button type="button" class="btn btn-warning btn-sm update-image-btn" data-id="${image.id}">Update</button>
                  <button type="button" class="btn btn-danger btn-sm delete-image-btn" data-id="${image.id}">Delete</button>
                </div>
              </div>`;
          });
          imagesHtml += '</div>';

          list.innerHTML += `
            <div class="card mb-3">
              <div class="card-body">
                <h5>${section.side_heading}</h5>
                <div>${section.description || ''}</div>
                <p class="mt-2">
                  <button type="button" class="btn btn-sm btn-warning edit-section-btn" data-id="${section.id}">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger delete-section-btn" data-id="${section.id}">Delete</button>
                  <button type="button" class="btn btn-sm btn-info upload-images-btn" data-id="${section.id}" data-heading="${section.side_heading}">Upload Images</button>
                </p>
                <div class="image-previews mt-2">${imagesHtml}</div>
              </div>
            </div>`;
        });
      } else {
        list.innerHTML = '<p>No sections found for this place. Add one above.</p>';
      }
    })
    .catch(err => {
      console.error(err);
      showMessage('Error loading sections.', 'danger');
    });
}

document.addEventListener('DOMContentLoaded', () => {
  if (placeId) fetchSections(placeId);
});

// Save main
document.getElementById('main-form').addEventListener('submit', function (e) {
  e.preventDefault();
  if (CKEDITOR.instances['main_description']) {
    CKEDITOR.instances['main_description'].updateElement();
  }
  const formData = new FormData(this);
  // IMPORTANT: main insert/update endpoint
  fetch('nearby_places/main/insert.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showMessage(data.message || 'Main details updated.', 'success');
      } else {
        showMessage('Error: ' + (data.error || 'Unable to save main details'), 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showMessage('Error saving main details.', 'danger');
    });
});

// Save section
document.getElementById('section-form').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('nearby_place_id', document.getElementById('section_place_id').value);
  formData.append('section_id', document.getElementById('section_id').value);
  formData.append('side_heading', document.getElementById('side_heading').value);
  formData.append('description', CKEDITOR.instances['description'].getData());

  fetch('nearby_places/sections/insert.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showMessage(data.message || 'Section saved.', 'success');
        fetchSections(placeId);
        resetSectionForm();
      } else {
        showMessage('Error: ' + (data.error || 'Unable to save section'), 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showMessage('Error saving section.', 'danger');
    });
});

document.getElementById('clear-section-btn').addEventListener('click', resetSectionForm);

// Delegated actions on sections list
document.getElementById('existing-sections-list').addEventListener('click', function(e) {
  // Edit Section
  if (e.target.classList.contains('edit-section-btn')) {
    const sectionId = e.target.dataset.id;
    fetch('nearby_places/sections/get.php?section_id=' + encodeURIComponent(sectionId))
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const section = data.data;
          document.getElementById('section_id').value = section.id;
          document.getElementById('side_heading').value = section.side_heading;
          CKEDITOR.instances['description'].setData(section.description || '');
          document.getElementById('save-section-btn').innerText = 'Update Section';
          const uploader = document.getElementById('section-image-uploader');
          uploader.style.display = 'block';
          document.getElementById('image_section_id').value = section.id;
          document.getElementById('section-heading-name').innerText = section.side_heading;
          document.getElementById('sections-container').scrollIntoView({behavior: 'smooth'});
        } else {
          showMessage('Section not found.', 'danger');
        }
      })
      .catch(err => {
        console.error(err);
        showMessage('Error loading section.', 'danger');
      });
  }

  // Delete Section
  if (e.target.classList.contains('delete-section-btn')) {
    if (!confirm('Delete this section and all its images?')) return;
    const sectionId = e.target.dataset.id;
    fetch('nearby_places/sections/delete.php?id=' + encodeURIComponent(sectionId))
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showMessage(data.message || 'Section deleted.', 'success');
          fetchSections(placeId);
        } else {
          showMessage('Error: ' + (data.error || 'Unable to delete section'), 'danger');
        }
      })
      .catch(err => {
        console.error(err);
        showMessage('Error deleting section.', 'danger');
      });
  }

  // Show uploader for section
  if (e.target.classList.contains('upload-images-btn')) {
    const sectionId = e.target.dataset.id;
    const heading = e.target.dataset.heading;
    document.getElementById('image_section_id').value = sectionId;
    document.getElementById('section-heading-name').innerText = heading;
    const uploader = document.getElementById('section-image-uploader');
    uploader.style.display = 'block';
    uploader.scrollIntoView({behavior: 'smooth'});
  }

  // Delete single image
  if (e.target.classList.contains('delete-image-btn')) {
    if (!confirm('Delete this image?')) return;
    const imageId = e.target.dataset.id;
    fetch('nearby_places/images/delete.php?id=' + encodeURIComponent(imageId))
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showMessage(data.message || 'Image deleted.', 'success');
          fetchSections(placeId);
        } else {
          showMessage('Error: ' + (data.error || 'Unable to delete image'), 'danger');
        }
      })
      .catch(err => {
        console.error(err);
        showMessage('Error deleting image.', 'danger');
      });
  }

  // Update single image
  if (e.target.classList.contains('update-image-btn')) {
    const imageId = e.target.dataset.id;
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(event) {
      if (event.target.files.length > 0) {
        const file = event.target.files[0];
        const formData = new FormData();
        formData.append('id', imageId);
        formData.append('image', file);
        fetch('nearby_places/images/update.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              showMessage(data.message || 'Image updated.', 'success');
              fetchSections(placeId);
            } else {
              showMessage('Error: ' + (data.error || 'Unable to update image'), 'danger');
            }
          })
          .catch(err => {
            console.error(err);
            showMessage('Error updating image.', 'danger');
          });
      }
    };
    input.click();
  }
});

// Upload images for a section
document.getElementById('image-form').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('nearby_places/images/insert.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showMessage(data.message || 'Images uploaded successfully.', 'success');
        fetchSections(placeId);
        this.reset();
      } else {
        showMessage('Error: ' + (data.error || 'Unable to upload images'), 'danger');
      }
    })
    .catch(err => {
      console.error(err);
      showMessage('Error uploading images.', 'danger');
    });
});
</script>
</body>
</html>
