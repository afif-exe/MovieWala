<?php
require_once 'config.php';
if (!is_logged_in()) {
    redirect('login.php');
}
$message = '';
$user_id = $_SESSION['user_id'];
// defining SVG icons for reuse
$icons = [
    'check' => '<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>',
    'close' => '<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg>',
    'star' => '<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="m233-80 65-281L80-550l288-25 112-265 112 265 288 25-218 189 65 281-247-149L233-80Z"/></svg>',
    'rocket' => '<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor"><path d="M200-120 40-280l160-160 57 56-64 64h387v-400l-64 64-56-57L640-893l160 160-57 56-64-64v480H292l64 64-56 57Z"/></svg>',
    'history' => '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24" fill="currentColor"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h80v-80q0-33 23.5-56.5T360-960h240q33 0 56.5 23.5T680-880v80h80q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Z"/></svg>'
];
// to handle subscription purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_subscription'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND end_date > CURDATE() AND payment_status = 'completed' ORDER BY end_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $active_sub = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $start_date = date('Y-m-d');
        $end_date = $active_sub ? date('Y-m-d', strtotime($active_sub['end_date'] . ' +1 month')) : date('Y-m-d', strtotime('+1 month'));
        
        $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, amount, payment_status, auto_renew) VALUES (?, 'premium', ?, ?, 0.99, 'completed', 1)");
        $stmt->execute([$user_id, $start_date, $end_date]);
        
        $message = '<span class="text-success">' . $icons['check'] . ' Subscription purchased successfully! Auto-renewal active for $0.99/month.</span>';
        
    } catch (PDOException $e) {
        $message = '<span class="text-danger">' . $icons['close'] . ' Error: ' . $e->getMessage() . '</span>';
    }
}
// to handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
    try {
        $stmt = $pdo->prepare("UPDATE subscriptions SET auto_renew = 0 WHERE user_id = ? AND end_date > CURDATE() AND payment_status = 'completed'");
        $stmt->execute([$user_id]);
        $message = '<span class="text-success">' . $icons['check'] . ' Auto-renewal cancelled.</span>';
    } catch (PDOException $e) {
        $message = '<span class="text-danger">' . $icons['close'] . ' Error: ' . $e->getMessage() . '</span>';
    }
}
//current subscription and history
$stmt=$pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND end_date > CURDATE() AND payment_status = 'completed' ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$current_subscription=$stmt->fetch(PDO::FETCH_ASSOC);
$stmt=$pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$subscription_history=$stmt->fetchAll(PDO::FETCH_ASSOC);

// premium features data
$premium_features = [
    ['icon' => 'close', 'title' => 'Ad-Free Experience', 'desc' => 'Enjoy browsing without any advertisements', 'color' => '#dc3545'],
    ['icon' => 'star', 'title' => 'Premium Movie Reviews', 'desc' => 'Access exclusive detailed movie reviews', 'color' => '#ffc107'],
    ['icon' => 'check', 'title' => 'Advanced Recommendations', 'desc' => 'AI-powered personalized movie suggestions', 'color' => '#007bff'],
    ['icon' => 'star', 'title' => 'Premium Badge', 'desc' => 'Show off your premium status on your profile', 'color' => '#ffc107'],
    ['icon' => 'check', 'title' => 'Premium Chatroom Access', 'desc' => 'Join exclusive discussions with other premium members', 'color' => '#007bff'],
    ['icon' => 'star', 'title' => 'Early Access', 'desc' => 'Be the first to try new features and updates', 'color' => '#ffc107'],
    ['icon' => 'check', 'title' => 'Enhanced Search', 'desc' => 'Advanced filters and search capabilities', 'color' => '#007bff'],
    ['icon' => 'star', 'title' => 'Exclusive Content', 'desc' => 'Access premium-only movie content and insights', 'color' => '#e91e63'],
    ['icon' => 'check', 'title' => 'Monthly Insights Report', 'desc' => 'Detailed analytics of your movie preferences', 'color' => '#28a745']
];
$page_title = "Subscription - MovieWala";
$additional_css = ['subscription.css'];
require_once INCLUDES_PATH . 'header.php';
?>
<div class="subscription-main-container">
    <h1 class="page-title">
        Premium Subscription
    </h1>   
    <div class="subscription-flex-container">
    <!-- Left Side: Subscription Management -->
        <div class="subscription-card">
            <?php if ($message): ?>
                <div class="message-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($current_subscription): ?>
            <!-- Active Subscription -->
                <div class="subscription-status-card">
                    <h2><?php echo str_replace('currentColor', '#28a745', $icons['check']); ?> You're Subscribed!</h2>
                    <p class="subscription-info">
                        Expires: <strong><?php echo date('F j, Y', strtotime($current_subscription['end_date'])); ?></strong>
                    </p>
                    <p class="auto-renew-status">
                        <?php if ($current_subscription['auto_renew']): ?>
                            <span class="text-success"><?php echo $icons['check']; ?> Auto-renewal ON - $0.99/month</span>
                        <?php else: ?>
                            <span class="text-danger"><?php echo $icons['close']; ?> Auto-renewal OFF</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($current_subscription['auto_renew']): ?>
                        <form method="POST" class="cancel-form">
                            <button type="submit" name="cancel_subscription" class="btn btn-danger" 
                                    onclick="return confirm('Cancel auto-renewal?')">
                                Cancel Auto-Renewal
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <!-- purchase subscription -->
                <div class="subscription-purchase-card">
                    <h2>Get Premium Access</h2>
                    <div class="price">$0.99<span>/month</span></div>
                    <p class="price-desc">Simple monthly subscription with auto-renewal</p>
                    
                    <form method="POST">
                        <button type="submit" name="buy_subscription" class="btn btn-primary btn-lg"
                                onclick="return confirm('Subscribe for $0.99/month with auto-renewal?')">
                            <?php echo str_replace('currentColor', 'white', $icons['rocket']); ?>
                            Subscribe Now
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Subscription History -->
            <?php if (!empty($subscription_history)): ?>
                <div class="subscription-history">
                    <h3><?php echo $icons['history']; ?> Subscription History</h3>
                    <div class="history-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Period</th>
                                    <th>Auto-Renew</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscription_history as $sub): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($sub['created_at'])); ?></td>
                                        <td class="amount">$<?php echo number_format($sub['amount'], 2); ?></td>
                                        <td><?php echo date('M j', strtotime($sub['start_date'])) . ' - ' . date('M j, Y', strtotime($sub['end_date'])); ?></td>
                                        <td>
                                            <?php if ($sub['auto_renew']): ?>
                                                <span class="text-success"><?php echo $icons['check']; ?> Yes</span>
                                            <?php else: ?>
                                                <span class="text-danger"><?php echo $icons['close']; ?> No</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Right Side: Premium Features -->
        <div class="premium-features-card">
            <h2 class="features-title">
                <?php echo str_replace('currentColor', '#ffc107', $icons['star']); ?>
                Premium Features
            </h2>
            
            <div class="features-list">
                <?php foreach ($premium_features as $feature): ?>
                    <div class="feature-item">
                        <div class="feature-icon" style="color: <?php echo $feature['color']; ?>">
                            <?php echo str_replace('currentColor', $feature['color'], $icons[$feature['icon']]); ?>
                        </div>
                        <div class="feature-content">
                            <h4><?php echo $feature['title']; ?></h4>
                            <p><?php echo $feature['desc']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="premium-cta">
                <h3>Why Choose Premium?</h3>
                <p>Join thousands of movie enthusiasts who have enhanced their MovieWala experience with premium features. Get more insights, better recommendations, and exclusive content for just $0.99/month!</p>
            </div>
        </div>
    </div>
</div>
<?php require_once INCLUDES_PATH .'footer.php'; ?>
