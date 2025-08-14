<?php
include 'session.php';
include 'db.php';

// Fetch existing blogs
$blogsRs = mysqli_query($conn, "SELECT * FROM blogs ORDER BY id DESC");

// Admin base URL to display uploaded images
$ADMIN_BASE_URL = '/ShivohamRetreat/admin/';
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
            <h2>Add / Manage Blogs</h2>

            <!-- ===== BLOG FORM ===== -->
            <form id="form-blog" method="POST" action="blogs/insert.php" enctype="multipart/form-data">
                <div class="card mb-4" id="section-blog">
                    <div class="card-header">Add Blog</div>
                    <div class="card-body">
                        <input type="hidden" name="id" id="blog-id" value="">
                        
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Slug (URL-friendly, auto-generated if empty)</label>
                            <input type="text" name="slug" id="slug" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Content</label>
                            <textarea name="content" id="content" class="form-control editor" rows="5"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Excerpt (Short description)</label>
                            <textarea name="excerpt" id="excerpt" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Category</label>
                            <input type="text" name="category" id="category" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Tags (comma separated)</label>
                            <input type="text" name="tags" id="tags" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Author</label>
                            <input type="text" name="author" id="author" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Featured Image</label><br>
                            <img id="preview_featured_image" src="" style="max-height:80px;display:none;margin-bottom:8px;">
                            <input type="file" name="featured_image" id="featured_image" class="form-control">
                            <input type="hidden" name="existing_featured_image" id="existing_featured_image" value="">
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" name="save_blog" id="btn-save-blog" class="btn btn-primary">Save Blog</button>
                        <button type="button" id="btn-update-blog" class="btn btn-success" style="display:none;">Update Blog</button>
                        <button type="button" id="btn-cancel-blog" class="btn btn-secondary" style="display:none;">Cancel Edit</button>
                    </div>
                </div>
            </form>

            <!-- ===== EXISTING BLOGS ===== -->
            <div class="row mb-4">
                <?php while ($b = mysqli_fetch_assoc($blogsRs)): ?>
                    <div class="col-md-4 mb-3" id="blog-card-<?= $b['id'] ?>">
                        <div class="card p-2">
                            <h5><?= htmlspecialchars($b['title']) ?></h5>
                            <?php if (!empty($b['featured_image'])): ?>
                                <img src="<?= $ADMIN_BASE_URL . htmlspecialchars($b['featured_image']) ?>" class="img-fluid mb-2" style="max-height:90px;">
                            <?php endif; ?>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary btn-edit-blog" data-id="<?= $b['id'] ?>">Edit</button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-blog" data-id="<?= $b['id'] ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        </main>
    </div>
</div>

<script>
CKEDITOR.replace('content');

// Edit blog
document.addEventListener('click', async function(e){
    if(e.target && e.target.classList.contains('btn-edit-blog')){
        const id = e.target.getAttribute('data-id');
        const res = await fetch('blogs/get.php?id=' + id);
        const data = await res.json();
        if(data.success){
            document.getElementById('blog-id').value = data.row.id;
            document.getElementById('title').value = data.row.title;
            document.getElementById('slug').value = data.row.slug;
            CKEDITOR.instances['content'].setData(data.row.content || '');
            document.getElementById('excerpt').value = data.row.excerpt || '';
            document.getElementById('category').value = data.row.category || '';
            document.getElementById('tags').value = data.row.tags || '';
            document.getElementById('author').value = data.row.author || '';
            
            if(data.row.featured_image){
                document.getElementById('preview_featured_image').src = '<?= $ADMIN_BASE_URL ?>' + data.row.featured_image;
                document.getElementById('preview_featured_image').style.display = 'block';
                document.getElementById('existing_featured_image').value = data.row.featured_image;
            } else {
                document.getElementById('preview_featured_image').style.display = 'none';
            }

            document.getElementById('btn-save-blog').style.display = 'none';
            document.getElementById('btn-update-blog').style.display = 'inline-block';
            document.getElementById('btn-cancel-blog').style.display = 'inline-block';
            document.getElementById('section-blog').scrollIntoView({behavior:'smooth'});
        }
    }
});

// Update blog
document.getElementById('btn-update-blog').addEventListener('click', async function(){
    const fd = new FormData(document.getElementById('form-blog'));
    fd.set('content', CKEDITOR.instances['content'].getData());
    const res = await fetch('blogs/update.php', {method:'POST', body: fd});
    const json = await res.json();
    if(json.success) location.reload();
    else alert(json.error || 'Update failed');
});

// Cancel edit
document.getElementById('btn-cancel-blog').addEventListener('click', function(){
    location.reload();
});

// Delete blog
document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('btn-delete-blog')){
        const id = e.target.getAttribute('data-id');
        if(!confirm('Delete this blog?')) return;
        fetch('blogs/delete.php?id=' + id).then(r => r.json()).then(json => {
            if(json.success) document.getElementById('blog-card-' + id).remove();
            else alert(json.error || 'Delete failed');
        });
    }
});
</script>
<?php include 'includes/script.php'; ?>
</body>
</html>
