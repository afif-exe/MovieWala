<?php
require_once 'config.php';
if (!is_logged_in()) {
    redirect('login.php');
}
$error_message = '';
// To handle a new post submission
if (isset($_POST['create_post']) && !empty(trim($_POST['title'])) && !empty(trim($_POST['content']))) {
    try {
        $sql = "INSERT INTO forum_posts (post_title, post_description, user_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([trim($_POST['title']), trim($_POST['content']), $_SESSION['user_id']]);
        header('Location: forum.php');
        exit;
    } catch (PDOException $e) { }//to prevent resubmission 
    $error_message = "Error creating post: " . $e->getMessage();
}
// Handle reply submission
if (isset($_POST['create_reply']) && !empty(trim($_POST['reply']))) {
    try {
        $sql = "INSERT INTO forum_replies (post_id, reply_text, user_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['post_id'], trim($_POST['reply']), $_SESSION['user_id']]);
        header('Location: forum.php');
        exit;
    } catch (PDOException $e) {
        $error_message = "Error creating reply: " . $e->getMessage();
}}
$posts = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM forum_posts ORDER BY post_id DESC");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);    
    // Get username for each post
    foreach ($posts as &$post) {
        $user_stmt = $pdo->prepare("SELECT name FROM User WHERE userID = ?");
        $user_stmt->execute([$post['user_id']]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $post['username'] = $user ? $user['name'] : 'Anonymous';
    }
} catch (PDOException $e) {
    $error_message = "Error fetching posts: " . $e->getMessage();
}
$replies_by_post = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM forum_replies ORDER BY reply_id ASC");
    $stmt->execute();
    $all_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get username for each of the replies and organize them by posts
    foreach ($all_replies as $reply) {
        $user_stmt = $pdo->prepare("SELECT name FROM User WHERE userID = ?");
        $user_stmt->execute([$reply['user_id']]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $reply['username'] = $user ? $user['name'] : 'Anonymous';        
        $replies_by_post[$reply['post_id']][] = $reply;
}
} catch (PDOException $e) {
    $error_message = "Error fetching replies: " . $e->getMessage();
}
$page_title = "Forum - MovieWala";
$additional_css = ['forum.css'];
$additional_js = ['forum.js'];
$body_class = 'forum-page';
require_once INCLUDES_PATH . 'header.php';
?>
<div class="forum-wrapper">
    <div class="forum-header">
        <div class="header-content">
            <a href="dashboard.php" class="back-link">
                <span class="icon-back">Back to Dashboard</span>
            </a>
            <div class="forum-title-section">
                <h1 class="forum-title">
                    <span class="icon-comments">Community Forum</span>
                </h1>
                <p class="forum-subtitle">Share your thoughts and connect with fellow movie enthusiasts</p>
            </div>
        </div>
    </div>

    <div class="forum-container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
<!-- New Post Form -->

        <div class="new-post-card">
            <div class="card-header">
                <h3>
                    <span class="icon-plus">Create New Post</span>
                </h3>
            </div>
            <div class="card-content">
                <form method="post" class="post-form">
                    <div class="form-group">
                        <input type="text" name="title" placeholder="What's your post about?" required minlength="3">
                    </div>
                    <div class="form-group">
                        <textarea name="content" placeholder="Share your thoughts..." required rows="4" minlength="5"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="create_post" class="btn-post">
                            <span class="icon-send">Publish Post</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    
<!-- Posts Display -->
 
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <div class="empty-icon" style="font-size:2em;"><span class="icon-comments"></span></div>
                    <h3>No posts yet!</h3>
                    <p>Be the first to start a conversation in our community.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $index => $post): ?>
                    <div class="post-card <?= $index === 0 ? 'featured-post' : '' ?>">
                        <div class="post-card-header">
                            <div class="author-info">
                                <div class="author-avatar" style="font-size:1.2em;"><span class="icon-user"></span></div>
                                <div class="author-details">
                                    <strong class="author-name"><?= htmlspecialchars($post['username'] ?: 'Anonymous') ?></strong>
                                    <span class="post-time"><span class="icon-clock"></span> Recently posted</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="post-card-body">
                            <h2 class="post-title"><?= htmlspecialchars($post['post_title']) ?></h2>
                            <div class="post-content"><?= nl2br(htmlspecialchars($post['post_description'])) ?></div>
                        </div>
                        
                        <?php if (!empty($replies_by_post[$post['post_id']])): ?>
                            <div class="replies-section">
                                <div class="replies-header">
                                    <h4><?= count($replies_by_post[$post['post_id']]) ?> Replies</h4>
                                    <button class="toggle-replies" onclick="toggleReplies(this)">
                                        ▼
                                    </button>
                                </div>
                                
                                <div class="replies-container">
                                    <?php foreach ($replies_by_post[$post['post_id']] as $reply): ?>
                                        <div class="reply-card">
                                            <div class="reply-header">
                                                <div class="reply-author">
                                                    <div class="reply-avatar" style="font-size:1em;"><span class="icon-user"></span></div>
                                                    <strong><?= htmlspecialchars($reply['username'] ?: 'Anonymous') ?></strong>
                                                </div>
                                                <span class="reply-time"><span class="icon-clock"></span> Recently</span>
                                            </div>
                                            <div class="reply-content"><?= nl2br(htmlspecialchars($reply['reply_text'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="reply-form-card">
                            <form method="post" class="reply-form">
                                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                <div class="reply-input-group">
                                    <div class="reply-avatar" style="font-size:1em;"><span class="icon-user"></span></div>
                                    <textarea name="reply" placeholder="Write a thoughtful reply..." required rows="2"></textarea>
                                    <button type="submit" name="create_reply" class="btn-reply">
                                        <span class="icon-send"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
