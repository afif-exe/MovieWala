<?php
// includes/header.php - Common header template
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'MovieWala'; ?></title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/main.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="assets/css/<?php echo $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional head content -->
    <?php if (isset($additional_head)): ?>
        <?php echo $additional_head; ?>
    <?php endif; ?>
</head>
<body<?php echo isset($body_class) ? ' class="' . $body_class . '"' : ''; ?>>
    <?php if (!isset($hide_header) || !$hide_header): ?>
    <div class="header">
        <a href="dashboard.php" class="logo">
            <span style="display:flex;align-items:center;gap:8px;">
                <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M6.271 5.055a.5.5 0 0 1 .52.038L11 7.055a.5.5 0 0 1 0 .89L6.791 9.907a.5.5 0 0 1-.791-.39V5.5a.5.5 0 0 1 .271-.445z"/>
                </svg>
                MovieWala
            </span>
        </a>
        <div>
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="movies.php">Movies</a>
                <a href="subscription.php" class="btn btn-primary" style="margin-left:15px; padding:8px 16px; border-radius:5px;">Premium</a>
                <a href="forum.php">Forum</a>
                <a href="profile.php">Profile</a>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger" style="margin-left:15px; padding:8px 18px; border-radius:6px; font-size:16px; text-decoration:none;">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
