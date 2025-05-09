<?php
// Start session and include necessary files
session_start();
include_once '../includes/db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Redirect to the home page if not an admin
    header('Location: ../index.php');
    exit();
}

// Get summary statistics
// Total products
$result = $conn->query("SELECT COUNT(*) as total_products FROM Products");
$total_products = $result->fetch_assoc()['total_products'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total_orders FROM Orders");
$total_orders = $result->fetch_assoc()['total_orders'];

// Total users
$result = $conn->query("SELECT COUNT(*) as total_users FROM Users WHERE is_admin = 0");
$total_users = $result->fetch_assoc()['total_users'];

// Recent orders
$result = $conn->query("SELECT o.order_id, o.order_date, o.total_amount, o.status, u.username 
                        FROM Orders o 
                        JOIN Users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $result->fetch_all(MYSQLI_ASSOC);

// Generate a version string for cache busting for admin pages
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css'; // Correct path from admin folder
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nova Gaming</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo $admin_css_version; ?>">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="../index.php">Nova Gaming</a></h1>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="../index.php">View Site</a></li>
                    <li><a href="manage_products.php">Products</a></li>
                    <li><a href="manage_orders.php">Orders</a></li>
                    <li><a href="manage_users.php">Users</a></li>
                    <li><a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container main-content admin-container">
        <aside class="admin-sidebar">
            <ul class="admin-navigation">
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="add_product.php">Add New Product</a></li>
                <li><a href="manage_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h1>Admin Dashboard</h1>
            
            <div class="admin-stats">
                <div class="stat-box">
                    <h3>Products</h3>
                    <p class="stat-number"><?php echo $total_products; ?></p>
                    <a href="manage_products.php" class="btn btn-sm">View All</a>
                </div>
                
                <div class="stat-box">
                    <h3>Orders</h3>
                    <p class="stat-number"><?php echo $total_orders; ?></p>
                    <a href="manage_orders.php" class="btn btn-sm">View All</a>
                </div>
                
                <div class="stat-box">
                    <h3>Customers</h3>
                    <p class="stat-number"><?php echo $total_users; ?></p>
                    <a href="manage_users.php" class="btn btn-sm">View All</a>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                
                <?php if (empty($recent_orders)): ?>
                    <p>No orders yet.</p>
                <?php else: ?>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Admin Panel</h3>
                    <p>Manage your store content, products, and orders.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Nova Gaming. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>