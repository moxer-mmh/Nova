<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once dirname(__FILE__) . '/db.php';
require_once 'currency_format.php'; // Add this line to include the currency formatter

// Calculate base URL for assets and navigation
$document_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$app_folder = rtrim(str_replace('\\', '/', dirname(dirname(__FILE__))), '/');
$base_url = str_replace($document_root, '', $app_folder);
if ($base_url === $app_folder) { // Fallback if DOCUMENT_ROOT is not part of app_folder (e.g. CLI or misconfig)
    $base_url = ''; // Assume root or handle error as appropriate
}

// Get cart item count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_query = $conn->prepare("SELECT SUM(quantity) as total_items FROM Cart WHERE user_id = ?");
    $cart_query->bind_param("i", $user_id);
    $cart_query->execute();
    $cart_result = $cart_query->get_result();
    if ($row = $cart_result->fetch_assoc()) {
        $cart_count = $row['total_items'] ?? 0;
    }
    $cart_query->close();
}

// Generate a version string for cache busting based on file modification time
$css_file_path = dirname(dirname(__FILE__)) . '/assets/css/style.css';
$css_version = file_exists($css_file_path) ? filemtime($css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Gaming - Your Gaming Equipment Store</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/assets/css/style.css?v=<?php echo $css_version; ?>">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="<?php echo htmlspecialchars($base_url); ?>/index.php">Nova Gaming</a></h1>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation menu">
                <i class="fas fa-bars"></i>☰
            </button>
            <nav>
                <ul class="nav-links">
                    <li><a href="<?php echo htmlspecialchars($base_url); ?>/index.php">Home</a></li>
                    <li><a href="<?php echo htmlspecialchars($base_url); ?>/products.php">Products</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($base_url); ?>/cart.php">Cart <?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?></a>
                        </li>
                        <li><a href="<?php echo htmlspecialchars($base_url); ?>/orders.php">My Orders</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li><a href="admin/index.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo htmlspecialchars($base_url); ?>/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo htmlspecialchars($base_url); ?>/login.php">Login</a></li>
                        <li><a href="<?php echo htmlspecialchars($base_url); ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container main-content">
        <?php
        // Display flash messages if any
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>