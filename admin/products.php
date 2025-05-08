<?php
// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Process delete product request
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    $stmt = $conn->prepare("DELETE FROM Products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete product.";
    }
    
    $stmt->close();
    
    // Redirect to refresh the page
    header("Location: products.php");
    exit();
}

// Get all products
$sql = "SELECT product_id, name, price, stock, category FROM Products ORDER BY name";
$result = $conn->query($sql);
$products = $result->fetch_all(MYSQLI_ASSOC);

// Generate a version string for cache busting for admin pages
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css'; // Correct path from admin folder
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Nova Gaming Admin</title>
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
                    <li><a href="products.php">Products</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container main-content admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php" class="active">Manage Products</a></li>
                <li><a href="add_product.php">Add New Product</a></li>
                <li><a href="orders.php">Manage Orders</a></li>
                <li><a href="users.php">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <a href="add_product.php" class="btn">Add New Product</a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (empty($products)): ?>
                <p>No products found in the database.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm">Edit</a>
                                    <form method="post" action="products.php" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <p>&copy; 2025 Nova Gaming. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>