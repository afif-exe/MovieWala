<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Handle chatroom message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message']) && !empty(trim($_POST['chat_message']))) {
    $chat_message = trim($_POST['chat_message']);
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO Chatroom (userID, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $chat_message]);
}

// Get recent chatroom messages - simple approach
$chat_stmt = $pdo->prepare("SELECT * FROM Chatroom ORDER BY created_at DESC LIMIT 50");
$chat_stmt->execute();
$chat_messages = $chat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get usernames for each message
foreach ($chat_messages as &$message) {
    $user_stmt = $pdo->prepare("SELECT name FROM User WHERE userID = ?");
    $user_stmt->execute([$message['userID']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $message['name'] = $user ? $user['name'] : 'Anonymous';
}

// Get recent movies for display
$stmt = $pdo->prepare("SELECT * FROM Movies ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$recent_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top rated movies - simple approach
$stmt = $pdo->prepare("SELECT * FROM Movies ORDER BY created_at DESC");
$stmt->execute();
$all_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$top_movies = [];
foreach ($all_movies as $movie) {
    // Get average rating for each movie
    $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as num_reviews FROM Reviews WHERE mID = ?");
    $rating_stmt->execute([$movie['mID']]);
    $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only include movies with reviews
    if ($rating_data['num_reviews'] > 0) {
        $movie['avg_rating'] = round($rating_data['avg_rating'], 1);
        $movie['num_reviews'] = $rating_data['num_reviews'];
        $top_movies[] = $movie;
    }
}

// Sort by rating and limit to 6
usort($top_movies, function($a, $b) {
    return $b['avg_rating'] <=> $a['avg_rating'];
});
$top_movies = array_slice($top_movies, 0, 6);

// Set page specific variables
$page_title = "Dashboard - MovieWala";
$additional_css = ['dashboard.css'];

// Include header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="page-layout">
    <!-- Chatroom Section -->
    <div class="chatroom-section">
        <div class="chatroom-title">Chatroom</div>
        <div class="chatroom-messages" id="chatroom-messages">
            <?php if (empty($chat_messages)): ?>
                <div style="color:#888;text-align:center;">No messages yet. Start the conversation!</div>
            <?php else: ?>
                <?php foreach (array_reverse($chat_messages) as $msg): ?>
                    <div class="chat-message">
                        <span class="chat-sender"><?php echo htmlspecialchars($msg['name']); ?></span>
                        <span class="chat-time"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></span>
                        <div class="chat-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form class="chatroom-form" method="POST" action="dashboard.php">
            <input type="text" name="chat_message" placeholder="Type your message..." maxlength="255" required>
            <button type="submit">Send</button>
        </form>
    </div>
    
    <!-- Centered Dashboard Content -->
    <div class="center-content">
        <div class="welcome-section">
            <h2>Welcome to Your Dashboard</h2>
            <p>Hello <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Welcome to the MovieWala. Here you can discover movies, write reviews, and connect with other movie enthusiasts.</p>
            
            <!-- Movie Search Bar -->
            <div class="search-section">
                <form method="GET" action="search_movies.php" class="search-form">
                    <input type="text" name="search" placeholder="Search movies by name..." required>
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960" width="18" fill="white" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                        </svg>
                        Search
                    </button>
                </form>
            </div>
            
            <div class="nav-links">
                <a href="movies.php">Browse Movies</a>
                <a href="profile.php">My Profile</a>
                <a href="add_review.php">Write Review</a>
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="movies-section">
            <h2>Top Movies</h2>
            <p>Check out some of the top rated movies in our database:</p>
            <?php if (empty($top_movies)): ?>
                <p>No rated movies found. <a href="movies.php">Add some movies</a> to get started!</p>
            <?php else: ?>
                <div class="movies-grid">
                    <?php foreach ($top_movies as $movie): ?>
                        <div class="movie-card" onclick="window.location.href='movie_details.php?id=<?php echo $movie['mID']; ?>'">
                            <h3><?php echo htmlspecialchars($movie['Name']); ?></h3>
                            <div class="movie-meta">
                                Rating: <?php echo number_format($movie['avg_rating'], 1); ?> |
                                Duration: <?php echo $movie['Duration']; ?> minutes | 
                                Released: <?php echo date('F j, Y', strtotime($movie['release_date'])); ?>
                            </div>
                            <div class="movie-description">
                                <?php echo htmlspecialchars(substr($movie['Description'], 0, 150)) . '...'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once INCLUDES_PATH . 'footer.php';
?>
