<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$movies = [];

if (!empty($search_query)) {
    // Search for movies by name - simple approach
    $stmt = $pdo->prepare("SELECT * FROM Movies WHERE Name LIKE ? ORDER BY Name ASC");
    $stmt->execute(["%$search_query%"]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get ratings for each movie separately
    foreach ($movies as &$movie) {
        // Get average rating
        $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM Reviews WHERE mID = ?");
        $rating_stmt->execute([$movie['mID']]);
        $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
        $movie['avg_rating'] = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
        $movie['review_count'] = $rating_data['review_count'];
        
        // Get user's rating
        $user_rating_stmt = $pdo->prepare("SELECT rating FROM Reviews WHERE mID = ? AND userID = ?");
        $user_rating_stmt->execute([$movie['mID'], $_SESSION['user_id']]);
        $user_rating = $user_rating_stmt->fetch(PDO::FETCH_ASSOC);
        $movie['user_rating'] = $user_rating ? $user_rating['rating'] : null;
    }
}

// Template variables
$page_title = 'Search Results - MovieWala';
$additional_css = ['search-movies.css'];
$additional_js = [];

include INCLUDES_PATH . 'header.php';
?>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="search-section">
            <h2>Search Movies</h2>
            <form method="GET" class="search-form">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search movies by name..." required>
                <button type="submit" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960" width="18" fill="white" style="vertical-align: middle; margin-right: 6px;">
                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                    Search
                </button>
            </form>
        </div>
        
        <?php if (!empty($search_query)): ?>
            <div class="results-header">
                <h3>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                <p>Found <?php echo count($movies); ?> movie(s)</p>
            </div>
            
            <?php if (empty($movies)): ?>
                <div class="no-results">
                    <h3>No movies found</h3>
                    <p>Sorry, no movies match your search criteria.</p>
                    <p>Try searching with different keywords or <a href="movies.php">browse all movies</a>.</p>
                </div>
            <?php else: ?>
                <div class="movies-grid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card" onclick="window.location.href='movie_details.php?id=<?php echo $movie['mID']; ?>'">
                            <div class="movie-content">
                                <div class="movie-title"><?php echo htmlspecialchars($movie['Name']); ?></div>
                                
                                <div class="movie-meta">
                                    <span class="genre-tag"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                    <span><?php echo $movie['Duration']; ?> min • <?php echo date('Y', strtotime($movie['release_date'])); ?></span>
                                </div>
                                
                                <div class="movie-rating">
                                    <div class="stars">
                                        <?php 
                                        $avg_rating = round($movie['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $avg_rating ? '<i class="fa fa-star"></i>' : '<i class="fa fa-star-o"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="rating-text">
                                        <?php echo number_format($movie['avg_rating'], 1); ?> 
                                        (<?php echo $movie['review_count']; ?> review<?php echo $movie['review_count'] != 1 ? 's' : ''; ?>)
                                    </div>
                                    <?php if ($movie['user_rating']): ?>
                                        <div class="user-rating">You rated: <?php echo $movie['user_rating']; ?><i class="fa fa-star"></i></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="movie-description">
                                    <?php echo htmlspecialchars(substr($movie['Description'], 0, 150)) . '...'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<?php include INCLUDES_PATH . 'footer.php'; ?>