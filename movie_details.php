<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movie_id <= 0) {
    redirect('movies.php');
}

// Get movie information - simple approach
$stmt = $pdo->prepare("SELECT * FROM Movies WHERE mID = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    redirect('movies.php');
}

// Get average rating and review count separately
$rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM Reviews WHERE mID = ?");
$rating_stmt->execute([$movie_id]);
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
$movie['avg_rating'] = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$movie['review_count'] = $rating_data['review_count'];

// Get user's existing review for this movie
$stmt = $pdo->prepare("SELECT * FROM Reviews WHERE userID = ? AND mID = ?");
$stmt->execute([$_SESSION['user_id'], $movie_id]);
$user_review = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all reviews for this movie (excluding current user's review)
$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name 
    FROM Reviews r 
    JOIN User u ON r.userID = u.userID 
    WHERE r.mID = ? AND r.userID != ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$movie_id, $_SESSION['user_id']]);
$other_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle review submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    // Validation
    if ($rating < 0 || $rating > 5) {
        $errors[] = "Please select a rating between 0 and 5 stars";
    }
    
    if (empty($review_text)) {
        $errors[] = "Please write a review";
    }
    
    if (empty($errors)) {
        // Only allow adding new reviews, not updating existing ones
        if (!$user_review) {
            // Insert new review
            $stmt = $pdo->prepare("INSERT INTO Reviews (userID, mID, rating, review_text) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$_SESSION['user_id'], $movie_id, $rating, $review_text]);
            $success = "Review added successfully!";
            
            if ($result) {
                // Refresh user review data
                $stmt = $pdo->prepare("SELECT * FROM Reviews WHERE userID = ? AND mID = ?");
                $stmt->execute([$_SESSION['user_id'], $movie_id]);
                $user_review = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Refresh movie data - simple approach
                $stmt = $pdo->prepare("SELECT * FROM Movies WHERE mID = ?");
                $stmt->execute([$movie_id]);
                $movie = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get updated rating data
                $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM Reviews WHERE mID = ?");
                $rating_stmt->execute([$movie_id]);
                $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
                $movie['avg_rating'] = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
                $movie['review_count'] = $rating_data['review_count'];
            }
        } else {
            $errors[] = "You have already reviewed this movie. Only one review per user is allowed.";
        }
    }
}

// Handle like/dislike actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['like_review'])) {
        $review_id = (int)$_POST['like_review'];
        // Only allow liking others' reviews
        $stmt = $pdo->prepare("SELECT userID FROM Reviews WHERE reviewID = ?");
        $stmt->execute([$review_id]);
        $review_user_id = $stmt->fetchColumn();
        if ($review_user_id && $review_user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO ReviewLikes (reviewID, userID) VALUES (?, ?)");
            $stmt->execute([$review_id, $_SESSION['user_id']]);
        }
        // Redirect to avoid form resubmission and update counts
        header("Location: movie_details.php?id=" . $movie_id);
        exit;
    }
    if (isset($_POST['dislike_review'])) {
        $review_id = (int)$_POST['dislike_review'];
        $stmt = $pdo->prepare("DELETE FROM ReviewLikes WHERE reviewID = ? AND userID = ?");
        $stmt->execute([$review_id, $_SESSION['user_id']]);
        // Redirect to avoid form resubmission and update counts
        header("Location: movie_details.php?id=" . $movie_id);
        exit;
    }
}

// Set page specific variables
$page_title = htmlspecialchars($movie['Name']) . " - MovieWala";
$additional_css = ['movie-details.css'];
$additional_js = ['movie-details.js'];

// Include header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="movie-details-container">
    <a href="movies.php" class="back-btn">← Back to Movies</a>
    
    <div class="movie-header" style="display: flex; align-items: flex-start; gap: 32px;">
        <?php
            // Attempt to find poster by movie name (jpg or png), ignoring special characters
            $poster_name = preg_replace('/[^A-Za-z0-9 ]/', '', $movie['Name']);
            $poster_base = 'Posters/' . trim($poster_name);
            $poster_jpg = $poster_base . '.jpg';
            $poster_png = $poster_base . '.png';
            if (file_exists($poster_jpg)) {
                $poster_url = $poster_jpg;
            } elseif (file_exists($poster_png)) {
                $poster_url = $poster_png;
            } else {
                $poster_url = 'Posters/Background.png'; // fallback image
            }
        ?>
        <div class="movie-poster" style="flex: 0 0 220px;">
            <img src="<?php echo htmlspecialchars($poster_url); ?>" alt="<?php echo htmlspecialchars($movie['Name']); ?> Poster" style="width: 220px; height: 320px; object-fit: cover; border-radius: 10px; box-shadow: 0 2px 12px #0002;">
        </div>
        <div style="flex: 1;">
            <h1 class="movie-title"><?php echo htmlspecialchars($movie['Name']); ?></h1>
            <div class="movie-subtitle"><?php echo htmlspecialchars($movie['Description']); ?></div>
            <div class="movie-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($movie['avg_rating'], 1); ?></span>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $movie['review_count']; ?></span>
                    <div class="stat-label">Reviews</div>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $movie['Duration']; ?>m</span>
                    <div class="stat-label">Duration</div>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo htmlspecialchars($movie['genre']); ?></span>
                    <div class="stat-label">Genre</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="movie-content">
        <div class="movie-description">
            <h3>Synopsis</h3>
            <p class="description-text"><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>
            
            <h4>Movie Details</h4>
            <ul class="movie-details-list">
                <li>
                    <span class="detail-label">Release Date:</span>
                    <span class="detail-value"><?php echo date('F j, Y', strtotime($movie['release_date'])); ?></span>
                </li>
                <li>
                    <span class="detail-label">Genre:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($movie['genre']); ?></span>
                </li>
                <li>
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value"><?php echo $movie['Duration']; ?> minutes</span>
                </li>
                <li>
                    <span class="detail-label">Average Rating:</span>
                    <span class="detail-value"><?php echo number_format($movie['avg_rating'], 1); ?>/5 (<?php echo $movie['review_count']; ?> reviews)</span>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Review Section -->
    <div class="review-section">
        <h3><?php echo $user_review ? 'Your Review' : 'Write a Review'; ?></h3>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <!-- Review Section -->
    <div class="review-section">
        <h3><?php echo $user_review ? 'Your Review' : 'Write a Review'; ?></h3>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$user_review): ?>
        <form method="POST" action="" class="review-form" onsubmit="return validateReviewForm()">
            <div class="rating-input">
                <label>Your Rating:</label>
                <div class="star-rating">
                    <span class="star" data-rating="1"><i class="fa fa-star"></i></span>
                    <span class="star" data-rating="2"><i class="fa fa-star"></i></span>
                    <span class="star" data-rating="3"><i class="fa fa-star"></i></span>
                    <span class="star" data-rating="4"><i class="fa fa-star"></i></span>
                    <span class="star" data-rating="5"><i class="fa fa-star"></i></span>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="0">
            </div>
            
            <div class="form-group">
                <label for="review_text">Your Review:</label>
                <textarea 
                    name="review_text" 
                    id="review_text" 
                    class="review-textarea" 
                    placeholder="Share your thoughts about this movie..."
                    maxlength="1000"
                    required
                ></textarea>
            </div>
            
            <button type="submit" name="submit_review" class="btn btn-primary">
                Submit Review
            </button>
        </form>
        <?php endif; ?>
        
        <!-- Other Reviews -->
        <?php if (!empty($other_reviews) || $user_review): ?>
            <h4>All Reviews (<?php echo count($other_reviews) + ($user_review ? 1 : 0); ?>)</h4>
            
            <?php if ($user_review): ?>
                <div class="review-item user-review">
                    <div class="review-header">
                        <div class="reviewer-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $user_review['rating'] ? 'filled' : ''; ?>"><i class="fa fa-star"></i></span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-date"><?php echo date('M j, Y', strtotime($user_review['created_at'])); ?></div>
                    </div>
                    <div class="review-text"><?php echo nl2br(htmlspecialchars($user_review['review_text'])); ?></div>
                </div>
            <?php endif; ?>
            
            <?php foreach ($other_reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-name">
                            <a href="profile.php?user=<?php echo $review['userID']; ?>">
                                <?php echo htmlspecialchars($review['reviewer_name']); ?>
                            </a>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"><i class="fa fa-star"></i></span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #666; font-style: italic; padding: 40px;">
                No reviews yet. Be the first to review this movie!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once INCLUDES_PATH . 'footer.php';
?>
