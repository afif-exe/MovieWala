<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// Build query based on filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.Name LIKE ? OR m.Description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($genre_filter)) {
    $where_conditions[] = "m.genre = ?";
    $params[] = $genre_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Determine sort order
$order_clause = "ORDER BY ";
switch ($sort) {
    case 'name':
        $order_clause .= "m.Name ASC";
        break;
    case 'year':
        $order_clause .= "m.release_date DESC";
        break;
    case 'rating':
        $order_clause .= "avg_rating DESC, m.Name ASC";
        break;
    default:
        $order_clause .= "m.Name ASC";
}

// Get movies with average ratings
$query = "
    SELECT m.*, 
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(r.reviewID) as review_count,
           ur.rating as user_rating
    FROM Movies m
    LEFT JOIN Reviews r ON m.mID = r.mID
    LEFT JOIN Reviews ur ON m.mID = ur.mID AND ur.userID = ?
    $where_clause
    GROUP BY m.mID
    $order_clause
";

array_unshift($params, $_SESSION['user_id']);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all genres for filter dropdown
$genre_stmt = $pdo->query("SELECT DISTINCT genre FROM Movies ORDER BY genre");
$genres = $genre_stmt->fetchAll(PDO::FETCH_COLUMN);

// Set page specific variables
$page_title = "Movies - MovieWala";
$additional_css = ['movies.css'];

// Include header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="movies-container">
    <div class="movies-header">
        <h1 class="movies-title">Browse Movies</h1>
        <p>Discover and rate movies from our extensive collection</p>
        
        <!-- Search and Filter Section -->
        <form method="GET" action="movies.php" class="movies-filters">
            <input type="text" name="search" placeholder="Search movies..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="genre">
                <option value="">All Genres</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo $genre_filter === $genre ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort">
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                <option value="year" <?php echo $sort === 'year' ? 'selected' : ''; ?>>Sort by Year</option>
                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Sort by Rating</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>
    
    <div class="movies-list">
        <?php if (empty($movies)): ?>
            <div class="no-movies">
                <h3>No movies found</h3>
                <p>Try adjusting your search criteria or <a href="movies.php">browse all movies</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($movies as $movie): ?>
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
                <div class="movie-item" onclick="window.location.href='movie_details.php?id=<?php echo $movie['mID']; ?>'" style="display: flex; align-items: flex-start; gap: 20px; cursor: pointer;">
                    <div class="movie-poster" style="flex: 0 0 120px;">
                        <img src="<?php echo htmlspecialchars($poster_url); ?>" alt="<?php echo htmlspecialchars($movie['Name']); ?> Poster" style="width: 120px; height: 180px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px #0002;">
                    </div>
                    <div class="movie-info" style="flex: 1;">
                        <h3><?php echo htmlspecialchars($movie['Name']); ?></h3>
                        <div class="movie-details">
                            <?php echo htmlspecialchars($movie['genre']); ?> |
                            <?php echo $movie['Duration']; ?> minutes |
                            Released: <?php echo date('F j, Y', strtotime($movie['release_date'])); ?>
                        </div>
                        <div class="movie-description">
                            <?php echo htmlspecialchars(substr($movie['Description'], 0, 200)) . '...'; ?>
                        </div>
                        <div class="movie-tags">
                            <span class="genre-tag"><?php echo htmlspecialchars($movie['genre']); ?></span>
                        </div>
                        <div class="rating-section">
                            <?php if ($movie['avg_rating'] > 0): ?>
                                <div class="rating-display"><?php echo number_format($movie['avg_rating'], 1); ?>/5</div>
                                <div class="rating-stars">
                                    <?php
                                    $full_stars = floor($movie['avg_rating']);
                                    $half_star = ($movie['avg_rating'] - $full_stars) >= 0.5;
                                    for ($i = 0; $i < $full_stars; $i++) echo '★';
                                    if ($half_star) echo '☆';
                                    for ($i = 0; $i < (5 - $full_stars - ($half_star ? 1 : 0)); $i++) echo '☆';
                                    ?>
                                </div>
                                <div style="font-size: 12px; color: #666;"><?php echo $movie['review_count']; ?> reviews</div>
                            <?php else: ?>
                                <div class="rating-display">No ratings</div>
                                <div style="font-size: 12px; color: #666;">Be the first to rate!</div>
                            <?php endif; ?>
                            <?php if ($movie['user_rating']): ?>
                                <div class="user-rating">Your rating: <?php echo $movie['user_rating']; ?>/5</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once INCLUDES_PATH . 'footer.php';
?>
