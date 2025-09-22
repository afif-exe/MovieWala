<?php
// config.php - Database configuration and common functions
session_start();

// Define base paths
define('BASE_PATH', __DIR__ . '/');
define('ASSETS_PATH', BASE_PATH . 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('INCLUDES_PATH', BASE_PATH . 'includes/');
define('TEMPLATES_PATH', BASE_PATH . 'templates/');

// Define base URL (adjust as needed for your setup)
define('BASE_URL', '/Cse370-Lab-project/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');

$host = 'localhost';
$dbname = 'movie_review_db';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create forum tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_posts (
            post_id INT AUTO_INCREMENT PRIMARY KEY,
            post_title VARCHAR(255) NOT NULL,
            post_description TEXT NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_replies (
            reply_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            reply_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE
        )
    ");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Helper function to redirect
function redirect($location) {
    header("Location: $location");
    exit();
}

// Function to update follower/following counts
function update_follow_counts($pdo, $userID) {
    // Update follower count
    $stmt = $pdo->prepare("UPDATE User SET follower = (SELECT COUNT(*) FROM UserFollows WHERE followingID = ?) WHERE userID = ?");
    $stmt->execute([$userID, $userID]);
    
    // Update following count
    $stmt = $pdo->prepare("UPDATE User SET following = (SELECT COUNT(*) FROM UserFollows WHERE followerID = ?) WHERE userID = ?");
    $stmt->execute([$userID, $userID]);
}

// Function to get user's top genres - simple approach
function get_user_top_genres($pdo, $userID, $limit = 5) {
    // Get all user's reviews
    $stmt = $pdo->prepare("SELECT mID FROM Reviews WHERE userID = ?");
    $stmt->execute([$userID]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count genres
    $genre_counts = [];
    foreach ($reviews as $review) {
        $movie_stmt = $pdo->prepare("SELECT genre FROM Movies WHERE mID = ?");
        $movie_stmt->execute([$review['mID']]);
        $genre = $movie_stmt->fetchColumn();
        
        if ($genre) {
            $genre_counts[$genre] = ($genre_counts[$genre] ?? 0) + 1;
        }
    }
    
    // Sort and limit
    arsort($genre_counts);
    $result = [];
    $count = 0;
    foreach ($genre_counts as $genre => $genre_count) {
        if ($count >= $limit) break;
        $result[] = ['genre' => $genre, 'count' => $genre_count];
        $count++;
    }
    
    return $result;
}

// Function to get user's reviewed movies
function get_user_reviews($pdo, $userID, $limit = null) {
    $limitClause = $limit ? "LIMIT " . (int)$limit : "";
    $stmt = $pdo->prepare("
        SELECT r.*, m.Name as movie_name, m.Duration, m.genre, m.release_date 
        FROM Reviews r 
        JOIN Movies m ON r.mID = m.mID 
        WHERE r.userID = ? 
        ORDER BY r.created_at DESC 
        $limitClause
    ");
    $stmt->execute([$userID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to check if user has active premium subscription
function is_premium_user($pdo, $userID) {
    $stmt = $pdo->prepare("SELECT is_premium, premium_expires FROM User WHERE userID = ?");
    $stmt->execute([$userID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_premium']) {
        return false;
    }
    
    // Check if premium has expired
    if ($user['premium_expires'] && $user['premium_expires'] <= date('Y-m-d')) {
        // Update user status if premium expired
        $updateStmt = $pdo->prepare("UPDATE User SET is_premium = 0 WHERE userID = ?");
        $updateStmt->execute([$userID]);
        return false;
    }
    
    return true;
}

// Function to get premium badge HTML
function get_premium_badge($pdo, $userID) {
    if (is_premium_user($pdo, $userID)) {
        return '<span style="background-color: #e74c3c; color: white; padding: 4px 8px; border-radius: 10px; font-size: 12px; font-weight: bold; margin-left: 5px;">PREMIUM</span>';
    }
    return '';
}
?>