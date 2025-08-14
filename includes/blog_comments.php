<?php
$blog_id = $blog['id'];

// Insert comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if ($name && $email && $comment) {
        $stmt = $conn->prepare("INSERT INTO blog_comments (blog_id, name, email, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $blog_id, $name, $email, $comment);
        $stmt->execute();
    }
}

// Fetch comments
$comments = $conn->query("SELECT * FROM blog_comments WHERE blog_id = $blog_id ORDER BY created_at DESC");
?>

<div class="comments-area">
    <h4><?php echo $comments->num_rows; ?> Comments</h4>
    <?php while ($c = $comments->fetch_assoc()): ?>
        <div class="comment-list">
            <div class="single-comment d-flex justify-content-between">
                <div class="user d-flex">
                    <div class="desc">
                        <p class="comment"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                        <div class="d-flex justify-content-between">
                            <div class="d-flex align-items-center">
                                <h5><?php echo htmlspecialchars($c['name']); ?></h5>
                                <p class="date"><?php echo date('F j, Y', strtotime($c['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<div class="comment-form">
    <h4>Leave a Reply</h4>
    <form method="POST" class="form-contact comment_form">
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <textarea class="form-control w-100" name="comment" cols="30" rows="9" placeholder="Write Comment"></textarea>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <input class="form-control" name="name" type="text" placeholder="Name" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <input class="form-control" name="email" type="email" placeholder="Email" required>
                </div>
            </div>
        </div>
        <div class="form-group mt-3">
            <button type="submit" name="comment_submit" class="button button-contactForm btn_1 boxed-btn">Send Message</button>
        </div>
    </form>
</div>
