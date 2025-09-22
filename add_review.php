<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$errors = [];
$success = '';
$movie_id = isset($_GET['movie']) ? (int)$_GET['movie'] : 0;

// Get movie information
$movie = null;
if ($movie_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM Movies WHERE mID = ?");
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$movie) {
    redirect('movies.php');
}

// Check if user already reviewed this movie
$stmt = $pdo->prepare("SELECT * FROM Reviews WHERE userID = ? AND mID = ?");
$stmt->execute([$_SESSION['user_id'], $movie_id]);
$existing_review = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5 stars";
    }
    
    if (empty($review_text)) {
        $errors[] = "Please write a review";
    }
    
    if (empty($errors)) {
        if ($existing_review) {
            // Update existing review
            $stmt = $pdo->prepare("UPDATE Reviews SET rating = ?, review_text = ?, created_at = CURRENT_TIMESTAMP WHERE userID = ? AND mID = ?");
            $result = $stmt->execute([$rating, $review_text, $_SESSION['user_id'], $movie_id]);
            $success = "Review updated successfully!";
        } else {
            // Insert new review
            $stmt = $pdo->prepare("INSERT INTO Reviews (userID, mID, rating, review_text) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$_SESSION['user_id'], $movie_id, $rating, $review_text]);
            $success = "Review added successfully!";
        }
        
        if ($result) {
            // Refresh existing review data
            $stmt = $pdo->prepare("SELECT * FROM Reviews WHERE userID = ? AND mID = ?");
            $stmt->execute([$_SESSION['user_id'], $movie_id]);
            $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Template variables
$page_title = 'Review: ' . htmlspecialchars($movie['Name']) . ' - MovieWala';
$additional_css = ['add-review.css'];
$additional_js = ['add-review.js'];

include INCLUDES_PATH . 'header.php';
?>
    <div class="container">
        <a href="movies.php" class="back-link">← Back to Movies</a>
        
        <div class="movie-info">
            <h1 class="movie-title"><?php echo htmlspecialchars($movie['Name']); ?></h1>
            <div class="movie-meta">
                <strong>Genre:</strong> <?php echo htmlspecialchars($movie['genre']); ?> | 
                <strong>Duration:</strong> <?php echo $movie['Duration']; ?> minutes | 
                <strong>Release Date:</strong> <?php echo date('F j, Y', strtotime($movie['release_date'])); ?>
            </div>
            <div class="movie-description">
                <?php echo htmlspecialchars($movie['Description']); ?>
            </div>
        </div>
        
        <div class="review-form">
            <h2><?php echo $existing_review ? 'Update Your Review' : 'Write a Review'; ?></h2>
            
            <?php if ($existing_review): ?>
                <div class="existing-review">
                    <h4>Your Current Review:</h4>
                    <div class="existing-rating">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $existing_review['rating'] ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($existing_review['review_text'])); ?></p>
                    <small>Last updated: <?php echo date('F j, Y g:i A', strtotime($existing_review['created_at'])); ?></small>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="rating-input">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star-rating" data-rating="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating" value="<?php echo $existing_review ? $existing_review['rating'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="review_text">Your Review:</label>
                    <textarea name="review_text" id="review_text" placeholder="Share your thoughts about this movie..."><?php echo $existing_review ? htmlspecialchars($existing_review['review_text']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    <?php echo $existing_review ? 'Update Review' : 'Submit Review'; ?>
                </button>
            </form>
        </div>
    </div>

<?php include INCLUDES_PATH . 'footer.php'; ?>