<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

include_once '../includes/db.php';
include_once '../includes/currency_format.php';

if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image_url FROM Products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $image_file = '../assets/images/' . $row['image_url'];
        if(file_exists($image_file)) {
            unlink($image_file);
        }
    }
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM Products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: manage_products.php?deleted=1');
    exit();
}

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

$result = $conn->query("SELECT COUNT(*) as total FROM Products");
$total_products = $result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

$stmt = $conn->prepare("SELECT * FROM Products ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css';
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
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="manage_products.php" class="active">Products</a></li>
                    <li><a href="manage_orders.php">Orders</a></li>
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
                <li><a href="manage_products.php" class="active">Manage Products</a></li>
                <li><a href="add_product.php">Add New Product</a></li>
                <li><a href="manage_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <a href="add_product.php" class="btn">Add New Product</a>
            </div>
            
            <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Product has been successfully deleted.</div>
            <?php endif; ?>
            
            <?php if(empty($products)): ?>
                <p>No products found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                            <tr>
                                <td data-label="ID"><?php echo $product['product_id']; ?></td>
                                <td data-label="Image">
                                    <img src="../assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="max-width: 50px; max-height: 50px;">
                                </td>
                                <td data-label="Name"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td data-label="Price"><?php echo formatCurrency($product['price']); ?></td>
                                <td data-label="Stock"><?php echo $product['stock']; ?></td>
                                <td data-label="Category"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td data-label="Actions">
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm">Edit</a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['product_id']; ?>)" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>" class="page-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $current_page): ?>
                                <span class="page-link current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>" class="page-link">Next &raquo;</a>
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
        function confirmDelete(productId) {
            if(confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                window.location.href = 'manage_products.php?delete=' + productId;
            }
        }
    </script>
</body>
</html>
