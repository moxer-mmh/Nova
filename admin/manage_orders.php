<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Include database connection and currency formatting
include_once '../includes/db.php';
include_once '../includes/currency_format.php'; // Add this line

// Handle order status update
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: manage_orders.php?updated=1');
    exit();
}

// Set up filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build the WHERE clause
$where_clause = "1=1";
$params = [];
$types = '';

if(!empty($status_filter)) {
    $where_clause .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($date_filter)) {
    $where_clause .= " AND DATE(o.order_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

// Set up pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Get total order count with filters
$count_sql = "SELECT COUNT(*) as total FROM Orders o WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if(!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_orders = $result->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_orders / $per_page);

// Get orders with pagination and filters
$sql = "SELECT o.*, u.username 
        FROM Orders o 
        JOIN Users u ON o.user_id = u.user_id 
        WHERE $where_clause 
        ORDER BY o.order_date DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get list of statuses for filter
$statuses_result = $conn->query("SELECT DISTINCT status FROM Orders");
$statuses = $statuses_result->fetch_all(MYSQLI_ASSOC);

// Get list of dates with orders for filter
$dates_result = $conn->query("SELECT DISTINCT DATE(order_date) as order_day FROM Orders ORDER BY order_day DESC");
$dates = $dates_result->fetch_all(MYSQLI_ASSOC);

// Generate a version string for cache busting
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css';
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Nova Gaming Admin</title>
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
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="manage_products.php">Products</a></li>
                    <li><a href="manage_orders.php" class="active">Orders</a></li>
                    <li><a href="manage_users.php">Users</a></li>
                    <li><a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container main-content admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="add_product.php">Add New Product</a></li>
                <li><a href="manage_orders.php" class="active">Manage Orders</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h1>Manage Orders</h1>
            
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success">Order status has been updated successfully.</div>
            <?php endif; ?>
            
            <div class="filters">
                <form action="" method="get" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="status">Filter by Status:</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <?php foreach($statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status['status']); ?>" <?php echo ($status_filter === $status['status']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status['status']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="date">Filter by Date:</label>
                            <select id="date" name="date" class="form-control">
                                <option value="">All Dates</option>
                                <?php foreach($dates as $date): ?>
                                    <option value="<?php echo $date['order_day']; ?>" <?php echo ($date_filter === $date['order_day']) ? 'selected' : ''; ?>>
                                        <?php echo date('F j, Y', strtotime($date['order_day'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-sm">Apply Filters</button>
                            <a href="manage_orders.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if(empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm" onclick="toggleStatusForm(<?php echo $order['order_id']; ?>)">Update Status</button>
                                    <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View Details</a>
                                    
                                    <div id="status-form-<?php echo $order['order_id']; ?>" class="status-form" style="display:none;">
                                        <form action="" method="post">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="status" class="form-control">
                                                <option value="Pending" <?php echo ($order['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo ($order['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo ($order['status'] === 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo ($order['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo ($order['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm">Save</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>" class="page-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $current_page): ?>
                                <span class="page-link current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . urlencode($date_filter) : ''; ?>" class="page-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
                <p>&copy; <?php echo date('Y'); ?> Nova Gaming. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function toggleStatusForm(orderId) {
            const form = document.getElementById('status-form-' + orderId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        // Auto-submit filters when changed
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-form select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    document.querySelector('.filter-form').submit();
                });
            });
        });
    </script>
</body>
</html>