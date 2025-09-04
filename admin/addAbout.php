<?php
// admin/addAbout.php
include 'session.php';
include 'db.php';

// fetch existing
$aboutMainRs = mysqli_query($conn, "SELECT * FROM about_1 ORDER BY id DESC");
$aboutInfoRs = mysqli_query($conn, "SELECT * FROM about_info ORDER BY id DESC");
$aboutSliderRs = mysqli_query($conn, "SELECT * FROM about_slider ORDER BY id DESC");

// admin base URL used to display uploaded images (adjust if your folder differs)
$ADMIN_BASE_URL = '/admin/';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<body class="sb-nav-fixed">
<?php include 'includes/navbar.php'; ?>
<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main class="container px-4 mt-4">
            <h2>Add / Manage About Page</h2>

            <!-- ===== MAIN SECTION FORM ===== -->
            <form id="form-main" method="POST" action="about_main/insert.php" enctype="multipart/form-data">
                <div class="card mb-4" id="section-main">
                    <div class="card-header">About Main Section</div>
                    <div class="card-body">
                        <input type="hidden" name="id" id="main-id" value="">
                        <div class="mb-3">
                            <label>Main Heading</label>
                            <input type="text" name="main_heading" id="main_heading" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Main Description</label>
                            <textarea name="main_description" id="main_description" class="form-control editor" rows="5"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Image 1</label><br>
                            <img id="preview_main_image1" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                            <input type="file" name="main_image1" id="main_image1" class="form-control">
                            <input type="hidden" name="existing_main_image1" id="existing_main_image1" value="">
                        </div>
                        <div class="mb-3">
                            <label>Image 2</label><br>
                            <img id="preview_main_image2" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                            <input type="file" name="main_image2" id="main_image2" class="form-control">
                            <input type="hidden" name="existing_main_image2" id="existing_main_image2" value="">
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" name="save_main" id="btn-save-main" class="btn btn-primary">Save Main Section</button>
                        <button type="button" id="btn-update-main" class="btn btn-success" style="display:none;">Update Main</button>
                        <button type="button" id="btn-cancel-main" class="btn btn-secondary" style="display:none;">Cancel Edit</button>
                    </div>
                </div>
            </form>

            <!-- show existing main records -->
            <div class="row mb-4">
                <?php while ($r = mysqli_fetch_assoc($aboutMainRs)): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card p-2">
                            <h5 class="mb-2"><?= htmlspecialchars($r['main_heading']) ?></h5>
                            <?php if (!empty($r['main_image1'])): ?>
                                <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($r['main_image1']) ?>" class="img-fluid mb-2" style="max-height:90px;">
                            <?php endif; ?>
                            <?php if (!empty($r['main_image2'])): ?>
                                <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($r['main_image2']) ?>" class="img-fluid mb-2" style="max-height:90px;">
                            <?php endif; ?>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary btn-edit-main" data-id="<?= $r['id'] ?>">Edit</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-main" data-id="<?= $r['id'] ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- ===== ABOUT 2 SECTION ===== -->
<form id="form-about2" method="POST" action="about_2/insert.php" enctype="multipart/form-data">
    <div class="card mb-4" id="section-about2">
        <div class="card-header">About Food</div>
        <div class="card-body">
            <input type="hidden" name="id" id="about2-id" value="">
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" id="about2-title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Heading</label>
                <input type="text" name="heading" id="about2-heading" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" id="about2-description" class="form-control editor" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label>Image 1</label><br>
                <img id="preview_about2_image1" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                <input type="file" name="image1" id="about2-image1" class="form-control">
                <input type="hidden" name="existing_image1" id="about2-existing-image1" value="">
            </div>
            <div class="mb-3">
                <label>Image 2</label><br>
                <img id="preview_about2_image2" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                <input type="file" name="image2" id="about2-image2" class="form-control">
                <input type="hidden" name="existing_image2" id="about2-existing-image2" value="">
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" name="save_about2" id="btn-save-about2" class="btn btn-primary">Save About 2</button>
            <button type="button" id="btn-update-about2" class="btn btn-success" style="display:none;">Update</button>
            <button type="button" id="btn-cancel-about2" class="btn btn-secondary" style="display:none;">Cancel</button>
        </div>
    </div>
</form>

<!-- Existing records -->
<div class="row mb-4">
<?php
$about2Rs = mysqli_query($conn, "SELECT * FROM about_2 ORDER BY id DESC");
while ($a2 = mysqli_fetch_assoc($about2Rs)): ?>
    <div class="col-md-4 mb-3">
        <div class="card p-2">
            <h5><?= htmlspecialchars($a2['heading']) ?></h5>
            <?php if (!empty($a2['image1'])): ?>
                <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($a2['image1']) ?>" class="img-fluid mb-2" style="max-height:90px;">
            <?php endif; ?>
            <?php if (!empty($a2['image2'])): ?>
                <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($a2['image2']) ?>" class="img-fluid mb-2" style="max-height:90px;">
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary btn-edit-about2" data-id="<?= $a2['id'] ?>">Edit</button>
                <button class="btn btn-sm btn-outline-danger btn-delete-about2" data-id="<?= $a2['id'] ?>">Delete</button>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>


            <!-- ===== INFO SECTION ===== -->
            <form id="form-info" method="POST" action="about_info/insert.php" enctype="multipart/form-data">
                <div class="card mb-4" id="section-info">
                    <div class="card-header">About Info Section</div>
                    <div class="card-body" id="infoContainer">
                        <div class="info-item border p-3 mb-3">
                            <input type="hidden" name="info_id[]" class="info_id" value="">
                            <div class="mb-2">
                                <label>Title</label>
                                <input type="text" name="info_title[]" class="form-control" required>
                            </div>
                            <div>
                                <label>Description</label>
                                <textarea name="info_description[]" class="form-control editor" id="info_editor_0" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button type="button" id="addMoreInfo" class="btn btn-outline-primary">+ Add More</button>
                        <div>
                            <button type="submit" name="save_info" class="btn btn-primary">Save Info Section</button>
                            <button type="button" id="btn-update-info" class="btn btn-success" style="display:none;">Update Info</button>
                            <button type="button" id="btn-cancel-info" class="btn btn-secondary" style="display:none;">Cancel Edit</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- existing info blocks -->
            <div class="row mb-4" id="existingInfoList">
                <?php
                // re-query to loop
                $tmp = mysqli_query($conn, "SELECT * FROM about_info ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($tmp)):
                ?>
                    <div class="col-md-4 mb-3" id="info-card-<?= $row['id'] ?>">
                        <div class="card p-2">
                            <h5><?= htmlspecialchars($row['info_title']) ?></h5>
                            <div><?= $row['info_description'] ?></div>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-outline-primary btn-edit-info" data-id="<?= $row['id'] ?>">Edit</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-info" data-id="<?= $row['id'] ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- ===== SLIDER SECTION ===== -->
            <form id="form-slider" method="POST" action="about_slider/insert.php" enctype="multipart/form-data">
                <div class="card mb-4" id="section-slider">
                    <div class="card-header">Slider Images</div>
                    <div class="card-body">
                        <label>Upload Images</label>
                        <input type="file" name="slider_images[]" class="form-control" multiple>
                        <small class="text-muted">You can select multiple images.</small>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="save_slider" class="btn btn-primary">Save Slider Images</button>
                    </div>
                </div>
            </form>

            <!-- existing slider images -->
            <div class="row" id="existingSliderList">
                <?php
                $tmp2 = mysqli_query($conn, "SELECT * FROM about_slider ORDER BY id DESC");
                while ($s = mysqli_fetch_assoc($tmp2)):
                ?>
                    <div class="col-md-3 mb-3" id="slider-card-<?= $s['id'] ?>">
                        <div class="card p-2 text-center">
                            <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($s['image']) ?>" class="img-fluid mb-2" style="max-height:100px;">
                            <div class="d-flex gap-2 justify-content-center">
                                <!-- <button class="btn btn-sm btn-outline-primary btn-edit-slider" data-id="<?= $s['id'] ?>">Edit</button> -->
                                <button class="btn btn-sm btn-outline-danger btn-delete-slider" data-id="<?= $s['id'] ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        </main>
    </div>
</div>

<!-- JS: CKEditor + AJAX behavior -->
<script>
    // initialize CKEditor for existing editor(s)
    CKEDITOR.replace('main_description');
    CKEDITOR.replace('info_editor_0');
    CKEDITOR.replace('about2-description');


    // helper to create unique editor ids
    let infoEditorIndex = 1;

    // add more info blocks UI
    document.getElementById('addMoreInfo').addEventListener('click', function(){
        const container = document.getElementById('infoContainer');
        const id = 'info_editor_' + infoEditorIndex;
        const div = document.createElement('div');
        div.className = 'info-item border p-3 mb-3 info-group';
        div.innerHTML = `
            <input type="hidden" name="info_id[]" class="info_id" value="">
            <div class="mb-2"><label>Title</label><input type="text" name="info_title[]" class="form-control" required></div>
            <div><label>Description</label><textarea name="info_description[]" class="form-control editor" id="${id}" rows="3" required></textarea></div>
            <div class="mt-2"><button type="button" class="btn btn-danger btn-sm removeInfo">Remove</button></div>
        `;
        container.appendChild(div);
        CKEDITOR.replace(id);
        infoEditorIndex++;
    });

    // remove info group
    document.addEventListener('click', function(e){
        if(e.target && e.target.classList.contains('removeInfo')){
            const group = e.target.closest('.info-group');
            const ta = group.querySelector('textarea');
            if(ta && CKEDITOR.instances[ta.id]) CKEDITOR.instances[ta.id].destroy();
            group.remove();
        }
    });

    // ---------- EDIT / DELETE handlers (AJAX) ----------

    // utility: fetch JSON
    async function fetchJSON(url){
        const res = await fetch(url);
        return res.json();
    }

    // ---- MAIN EDIT ----
    document.addEventListener('click', async function(e){
        if(e.target && e.target.classList.contains('btn-edit-main')){
            const id = e.target.getAttribute('data-id');
            const data = await fetchJSON('about_main/get.php?id=' + id);
            if(data.success){
                // populate main form
                document.getElementById('main-id').value = data.row.id;
                document.getElementById('main_heading').value = data.row.main_heading;
                CKEDITOR.instances['main_description'].setData(data.row.main_description || '');
                // preview images
                if(data.row.main_image1){
                    document.getElementById('preview_main_image1').src = '<?= $ADMIN_BASE_URL ?>' + data.row.main_image1;
                    document.getElementById('preview_main_image1').style.display = 'block';
                    document.getElementById('existing_main_image1').value = data.row.main_image1;
                } else {
                    document.getElementById('preview_main_image1').style.display = 'none';
                    document.getElementById('existing_main_image1').value = '';
                }
                if(data.row.main_image2){
                    document.getElementById('preview_main_image2').src = '<?= $ADMIN_BASE_URL ?>' + data.row.main_image2;
                    document.getElementById('preview_main_image2').style.display = 'block';
                    document.getElementById('existing_main_image2').value = data.row.main_image2;
                } else {
                    document.getElementById('preview_main_image2').style.display = 'none';
                    document.getElementById('existing_main_image2').value = '';
                }

                // toggle buttons: hide Save, show Update/Cancel
                document.getElementById('btn-save-main').style.display = 'none';
                document.getElementById('btn-update-main').style.display = 'inline-block';
                document.getElementById('btn-cancel-main').style.display = 'inline-block';
                // scroll to main
                document.getElementById('section-main').scrollIntoView({behavior:'smooth'});
            } else {
                alert('Record not found');
            }
        }
    });

    // update main: POST form to about_main/update.php using FormData
    document.getElementById('btn-update-main').addEventListener('click', async function(){
        const id = document.getElementById('main-id').value;
        if(!id){ alert('No main record selected'); return; }
        const fd = new FormData(document.getElementById('form-main'));
        // include id explicitly
        fd.append('id', id);
        // include description from CKEditor
        fd.set('main_description', CKEDITOR.instances['main_description'].getData());
        const res = await fetch('about_main/update.php', {method:'POST', body: fd});
        const json = await res.json();
        if(json.success){
            location.reload();
        } else {
            alert(json.error || 'Update failed');
        }
    });

    // cancel main edit
    document.getElementById('btn-cancel-main').addEventListener('click', function(){
        // reset form and toggles
        document.getElementById('form-main').reset();
        document.getElementById('main-id').value = '';
        CKEDITOR.instances['main_description'].setData('');
        document.getElementById('preview_main_image1').style.display = 'none';
        document.getElementById('preview_main_image2').style.display = 'none';
        document.getElementById('existing_main_image1').value = '';
        document.getElementById('existing_main_image2').value = '';
        document.getElementById('btn-save-main').style.display = 'inline-block';
        document.getElementById('btn-update-main').style.display = 'none';
        document.getElementById('btn-cancel-main').style.display = 'none';
    });

    // delete main
    document.addEventListener('click', function(e){
        if(e.target && e.target.classList.contains('btn-delete-main')){
            const id = e.target.getAttribute('data-id');
            if(!confirm('Delete this main record?')) return;
            fetch('about_main/delete_image.php?id=' + id).then(r => r.json()).then(json => {
                if(json.success) location.reload();
                else alert(json.error || 'Delete failed');
            });
        }
    });

    // ---- ABOUT 2 EDIT ----
document.addEventListener('click', async function(e){
    if(e.target && e.target.classList.contains('btn-edit-about2')){
        const id = e.target.getAttribute('data-id');
        const data = await fetchJSON('about_2/get.php?id=' + id);
        if(data.success){
            document.getElementById('about2-id').value = data.row.id;
            document.getElementById('about2-title').value = data.row.title;
            document.getElementById('about2-heading').value = data.row.heading;
            CKEDITOR.instances['about2-description'].setData(data.row.description || '');

            if(data.row.image1){
                document.getElementById('preview_about2_image1').src = '<?= $ADMIN_BASE_URL ?>' + data.row.image1;
                document.getElementById('preview_about2_image1').style.display = 'block';
                document.getElementById('about2-existing-image1').value = data.row.image1;
            } else {
                document.getElementById('preview_about2_image1').style.display = 'none';
                document.getElementById('about2-existing-image1').value = '';
            }

            if(data.row.image2){
                document.getElementById('preview_about2_image2').src = '<?= $ADMIN_BASE_URL ?>' + data.row.image2;
                document.getElementById('preview_about2_image2').style.display = 'block';
                document.getElementById('about2-existing-image2').value = data.row.image2;
            } else {
                document.getElementById('preview_about2_image2').style.display = 'none';
                document.getElementById('about2-existing-image2').value = '';
            }

            document.getElementById('btn-save-about2').style.display = 'none';
            document.getElementById('btn-update-about2').style.display = 'inline-block';
            document.getElementById('btn-cancel-about2').style.display = 'inline-block';
            document.getElementById('section-about2').scrollIntoView({behavior:'smooth'});
        } else {
            alert('Record not found');
        }
    }
});

// update about_2
document.getElementById('btn-update-about2').addEventListener('click', async function(){
    const fd = new FormData(document.getElementById('form-about2'));
    fd.append('id', document.getElementById('about2-id').value);
    fd.set('description', CKEDITOR.instances['about2-description'].getData());
    const res = await fetch('about_2/update.php', {method:'POST', body: fd});
    const json = await res.json();
    if(json.success) location.reload();
    else alert(json.error || 'Update failed');
});

// cancel about_2 edit
document.getElementById('btn-cancel-about2').addEventListener('click', function(){
    document.getElementById('form-about2').reset();
    document.getElementById('about2-id').value = '';
    CKEDITOR.instances['about2-description'].setData('');
    document.getElementById('preview_about2_image1').style.display = 'none';
    document.getElementById('preview_about2_image2').style.display = 'none';
    document.getElementById('about2-existing-image1').value = '';
    document.getElementById('about2-existing-image2').value = '';
    document.getElementById('btn-save-about2').style.display = 'inline-block';
    document.getElementById('btn-update-about2').style.display = 'none';
    document.getElementById('btn-cancel-about2').style.display = 'none';
});

// delete about_2
document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('btn-delete-about2')){
        const id = e.target.getAttribute('data-id');
        if(!confirm('Delete this record?')) return;
        fetch('about_2/delete.php?id=' + id)
        .then(r => r.json())
        .then(json => {
            if(json.success) location.reload();
            else alert(json.error || 'Delete failed');
        });
    }
});


    // ---- INFO EDIT ----
    document.addEventListener('click', async function(e){
        if(e.target && e.target.classList.contains('btn-edit-info')){
            const id = e.target.getAttribute('data-id');
            const data = await fetchJSON('about_info/get.php?id=' + id);
            if(data.success){
                // populate single editor group for update
                // clear existing dynamic groups
                const container = document.getElementById('infoContainer');
                container.innerHTML = '';
                const idEditor = 'info_edit_editor_0';
                const div = document.createElement('div');
                div.className = 'info-item border p-3 mb-3';
                div.innerHTML = `
                    <input type="hidden" name="info_id[]" class="info_id" value="${data.row.id}">
                    <div class="mb-2"><label>Title</label><input type="text" name="info_title[]" class="form-control" value="${escapeHtml(data.row.info_title)}" required></div>
                    <div><label>Description</label><textarea name="info_description[]" class="form-control editor" id="${idEditor}" rows="3" required>${data.row.info_description}</textarea></div>
                `;
                container.appendChild(div);
                CKEDITOR.replace(idEditor);
                CKEDITOR.instances[idEditor].setData(data.row.info_description || '');
                // show update button
                document.getElementById('btn-update-info').style.display = 'inline-block';
                document.getElementById('btn-cancel-info').style.display = 'inline-block';
                document.querySelector('#form-info').scrollIntoView({behavior:'smooth'});
            } else {
                alert('Info record not found');
            }
        }
    });

    // update info - send form via fetch to about_info/update.php
    document.getElementById('btn-update-info').addEventListener('click', async function(){
        // copy CKEditor content to textareas
        document.querySelectorAll('.editor').forEach(el => {
            if(el.id && CKEDITOR.instances[el.id]) {
                el.value = CKEDITOR.instances[el.id].getData();
            }
        });
        const fd = new FormData(document.getElementById('form-info'));
        const res = await fetch('about_info/update.php', {method:'POST', body: fd});
        const json = await res.json();
        if(json.success) location.reload();
        else alert(json.error || 'Update failed');
    });

    // cancel info edit (reload to restore)
    document.getElementById('btn-cancel-info').addEventListener('click', function(){
        location.reload();
    });

    // delete info
    document.addEventListener('click', function(e){
        if(e.target && e.target.classList.contains('btn-delete-info')){
            const id = e.target.getAttribute('data-id');
            if(!confirm('Delete this info block?')) return;
            fetch('about_info/delete.php?id=' + id).then(r => r.json()).then(json => {
                if(json.success){
                    const el = document.getElementById('info-card-' + id);
                    if(el) el.remove();
                } else alert(json.error || 'Delete failed');
            });
        }
    });

    // ---- SLIDER EDIT ----
    document.addEventListener('click', async function(e){
        if(e.target && e.target.classList.contains('btn-edit-slider')){
            const id = e.target.getAttribute('data-id');
            const data = await fetchJSON('about_slider/get.php?id=' + id);
            if(data.success){
                // populate slider upload form with single-file update option
                // we'll re-use the slider form: switch action to update and show update button
                const form = document.getElementById('form-slider');
                form.action = 'about_slider/insert.php'; // we will use update endpoint instead
                // create a hidden input slider_edit_id
                if(!document.getElementById('slider_edit_id')){
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'edit_id';
                    hidden.id = 'slider_edit_id';
                    form.appendChild(hidden);
                }
                document.getElementById('slider_edit_id').value = data.row.id;
                // Note: we can't preview multiple in same form easily; show preview maybe
                alert('Edit mode: choose a new file and click Save Slider Images to update this slider image (backend will detect edit_id).');
                form.scrollIntoView({behavior:'smooth'});
            } else {
                alert('Slider record not found');
            }
        }
    });

    // delete slider
    document.addEventListener('click', function(e){
        if(e.target && e.target.classList.contains('btn-delete-slider')){
            const id = e.target.getAttribute('data-id');
            if(!confirm('Delete this slider image?')) return;
            fetch('about_slider/delete.php?id=' + id).then(r => r.json()).then(json => {
                if(json.success){
                    const el = document.getElementById('slider-card-' + id);
                    if(el) el.remove();
                } else alert(json.error || 'Delete failed');
            });
        }
    });

    // helper to escape quotes in titles for JS population
    function escapeHtml(str){
        if(!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
</script>
<?php include 'includes/script.php'; ?>

</body>
</html>
