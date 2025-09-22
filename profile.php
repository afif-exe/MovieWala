<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$viewing_own_profile = true;

// Check if viewing another user's profile
if (isset($_GET['user']) && is_numeric($_GET['user'])) {
    $user_id = (int)$_GET['user'];
    $viewing_own_profile = ($user_id == $_SESSION['user_id']);
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM User WHERE userID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('dashboard.php');
}

// Get user's reviews
$user_reviews = get_user_reviews($pdo, $user_id, 10);

// Remove top genres - not needed

// Get review statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_reviews, AVG(rating) as avg_rating FROM Reviews WHERE userID = ?");
$stmt->execute([$user_id]);
$review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if current user is following this profile user
$is_following = false;
if (!$viewing_own_profile) {
    $stmt = $pdo->prepare("SELECT 1 FROM UserFollows WHERE followerID = ? AND followingID = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $stmt->fetchColumn() !== false;
}

// Handle follow/unfollow action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$viewing_own_profile) {
    if (isset($_POST['follow'])) {
        // Follow user
        $stmt = $pdo->prepare("INSERT IGNORE INTO UserFollows (followerID, followingID) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        $is_following = true;
    } elseif (isset($_POST['unfollow'])) {
        // Unfollow user
        $stmt = $pdo->prepare("DELETE FROM UserFollows WHERE followerID = ? AND followingID = ?");
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        $is_following = false;
    }
    
    // Update follower counts
    update_follow_counts($pdo, $user_id);
    update_follow_counts($pdo, $_SESSION['user_id']);
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM User WHERE userID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?>'s Profile - MovieWala</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
    <div class="header">
    <a href="dashboard.php" style="display:flex;align-items:center;text-decoration:none;">
        <span style="font-size:28px;font-weight:bold;color:#fff;display:flex;align-items:center;gap:8px;">
            🎬 MovieWala
        </span>
    </a>
    <div>
        <?php if (isset($_SESSION['user_name'])): ?>
            <a href="profile.php" style="color:#fff; margin-right:15px; text-decoration:none;">
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </a>
        <?php endif; ?>
        <a href="dashboard.php" style="color:white; text-decoration:none; margin-left:15px;">Dashboard</a>
        <a href="movies.php" style="color:white; text-decoration:none; margin-left:15px;">Movies</a>
        <a href="forum.php" style="color:white; text-decoration:none; margin-left:15px;">Forum</a>
        <a href="logout.php" style="background:#dc3545; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:16px; text-decoration:none; cursor:pointer; font-weight:500; margin-left:15px;">Logout</a>
    </div>
    </div>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Member since:</strong> <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                <p><strong>User Type:</strong> <?php echo ucfirst($user['type']); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $user['follower']; ?></span>
                        <span class="stat-label">Followers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $user['following']; ?></span>
                        <span class="stat-label">Following</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $review_stats['total_reviews'] ?: 0; ?></span>
                        <span class="stat-label">Reviews</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $review_stats['avg_rating'] ? number_format($review_stats['avg_rating'], 1) : 'N/A'; ?></span>
                        <span class="stat-label">Avg Rating</span>
                    </div>
                </div>
                
                <?php if (!$viewing_own_profile): ?>
                    <form method="post" style="margin-bottom:15px;">
                        <?php if ($is_following): ?>
                            <button type="submit" name="unfollow" style="background:#dc3545; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:16px; cursor:pointer;">Unfollow</button>
                        <?php else: ?>
                            <button type="submit" name="follow" style="background:#28a745; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:16px; cursor:pointer;">Follow</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="reviews-section">
                <div class="section">
                    <h3>Recent Reviews</h3>
                    <?php if (empty($user_reviews)): ?>
                        <div class="no-content">
                            <?php if ($viewing_own_profile): ?>
                                <p>You haven't written any reviews yet.</p>
                                <p><a href="movies.php">Browse movies</a> to start reviewing!</p>
                            <?php else: ?>
                                <p><?php echo htmlspecialchars($user['name']); ?> hasn't written any reviews yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="movie-title" ><?php echo htmlspecialchars($review['movie_name']); ?></div>
                                    <div class="rating">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '<i class="fa fa-star"></i>' : '<i class="fa fa-star-o"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="review-meta">
                                    <?php echo htmlspecialchars($review['genre']); ?> • 
                                    <?php echo $review['Duration']; ?> min • 
                                    Reviewed on <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                                <?php if ($review['review_text']): ?>
                                    <div class="review-text">
                                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar">
                <?php if ($viewing_own_profile): ?>
                <div class="section">
                    <h3>Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="movies.php" style="background-color: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center;">Browse Movies</a>
                        <a href="add_review.php" style="background-color: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center;">Write Review</a>
                        
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>