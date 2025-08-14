<?php
// Fetch categories
$categories = $conn->query("SELECT DISTINCT category FROM blogs ORDER BY category ASC");

// Fetch recent posts
$recent_posts = $conn->query("SELECT title, slug, featured_image, created_at FROM blogs ORDER BY created_at DESC LIMIT 5");

// Fetch tags (splitting comma-separated values)
$tags_result = $conn->query("SELECT tags FROM blogs WHERE tags IS NOT NULL AND tags != ''");
$all_tags = [];
while ($row = $tags_result->fetch_assoc()) {
    $tags = array_map('trim', explode(',', $row['tags']));
    $all_tags = array_merge($all_tags, $tags);
}
$all_tags = array_unique($all_tags);
?>

<div class="col-lg-4">
    <div class="blog_right_sidebar">

        <!-- Search Widget -->
        <aside class="single_sidebar_widget search_widget">
            <form action="blog.php" method="GET">
                <div class="form-group">
                    <div class="input-group mb-3">
                        <input type="text" name="search" class="form-control" placeholder="Search Keyword" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <div class="input-group-append">
                            <button class="btns" type="submit"><i class="ti-search"></i></button>
                        </div>
                    </div>
                </div>
                <button class="button rounded-0 primary-bg text-white w-100 btn_1 boxed-btn" type="submit">Search</button>
            </form>
        </aside>

        <!-- Category Widget -->
        <aside class="single_sidebar_widget post_category_widget">
            <h4 class="widget_title">Category</h4>
            <ul class="list cat-list">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <li>
                        <a href="blog.php?category=<?php echo urlencode($cat['category']); ?>" class="d-flex">
                            <p><?php echo htmlspecialchars($cat['category']); ?></p>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </aside>

        <!-- Recent Posts Widget -->
        <aside class="single_sidebar_widget popular_post_widget">
            <h3 class="widget_title">Recent Post</h3>
            <?php while ($post = $recent_posts->fetch_assoc()): ?>
                <div class="media post_item">
                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="post" style="width: 80px; height: 80px; object-fit: cover;">
                    <div class="media-body">
                        <a href="viewBlog.php?slug=<?php echo urlencode($post['slug']); ?>">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        </a>
                        <p><?php echo date('F j, Y', strtotime($post['created_at'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </aside>

        <!-- Tag Cloud Widget -->
        <aside class="single_sidebar_widget tag_cloud_widget">
            <h4 class="widget_title">Tag Clouds</h4>
            <ul class="list">
                <?php foreach ($all_tags as $tag): ?>
                    <li>
                        <a href="blog.php?tag=<?php echo urlencode($tag); ?>"><?php echo htmlspecialchars($tag); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </div>
</div>
