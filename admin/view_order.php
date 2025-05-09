<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Include database connection and currency formatting
include_once '../includes/db.php';
include_once '../includes/currency_format.php'; // Add this line

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Get order information
$stmt = $conn->prepare("SELECT o.*, u.username, u.full_name, u.email FROM Orders o 
                        JOIN Users u ON o.user_id = u.user_id 
                        WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_orders.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url FROM Order_Items oi 
                        JOIN Products p ON oi.product_id = p.product_id 
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Generate a version string for cache busting
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css';
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details - Nova Gaming Admin</title>
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
            <div class="admin-header">
                <h1>Order #<?php echo $order_id; ?> Details</h1>
                <a href="manage_orders.php" class="btn btn-secondary">Back to Orders</a>
            </div>
            
            <div class="order-details">
                <div class="order-meta">
                    <h2>Order Information</h2>
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Order Date:</span>
                            <span class="meta-value"><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Status:</span>
                            <span class="meta-value status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total Amount:</span>
                            <span class="meta-value"><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <h2>Customer Information</h2>
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Customer Name:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Username:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Email:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <form action="manage_orders.php" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <div class="form-group">
                                <label for="status">Update Status:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="Pending" <?php echo ($order['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Processing" <?php echo ($order['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo ($order['status'] === 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo ($order['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo ($order['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn">Update Status</button>
                        </form>
                    </div>
                </div>
                
                <div class="order-items">
                    <h2>Order Items</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/images/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             style="max-width: 60px; max-height: 60px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo formatCurrency($item['price_per_unit']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['price_per_unit'] * $item['quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
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
                <p>&copy; <?php echo date('Y'); ?> Nova Gaming. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <style>
        .order-details {
            background-color: #1f1f1f;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .meta-label {
            display: block;
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .meta-value {
            font-weight: 500;
            color: #e0e0e0;
        }
        .text-right {
            text-align: right;
        }
        .order-actions {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #333;
        }
    </style>
</body>
</html>
