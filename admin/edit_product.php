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

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];
$error = '';
$success = '';

// Get product data
$stmt = $conn->prepare("SELECT * FROM Products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if product exists
if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    
    // Validate form data
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || empty($category)) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Check if we need to update the image
        $image_url = $product['image_url']; // Keep existing image by default
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file extension
            if (in_array($file_ext, $allowed_ext)) {
                // Generate a unique filename
                $new_file_name = uniqid() . '.' . $file_ext;
                $upload_path = '../assets/images/' . $new_file_name;
                
                // Move the uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = $new_file_name;
                    
                    // Delete old image if it's not the default
                    if ($product['image_url'] !== 'default.jpg') {
                        $old_image_path = '../assets/images/' . $product['image_url'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid image format. Allowed formats: jpg, jpeg, png, gif.";
            }
        }
        
        // If no error, update the product in the database
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE Products SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, category = ? WHERE product_id = ?");
            $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $image_url, $category, $product_id);
            
            if ($stmt->execute()) {
                $success = "Product updated successfully!";
                
                // Refresh product data
                $stmt = $conn->prepare("SELECT * FROM Products WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();
            } else {
                $error = "Failed to update product: " . $conn->error;
            }
        }
    }
}

// Get all distinct categories for the dropdown
$result = $conn->query("SELECT DISTINCT category FROM Products ORDER BY category");
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Generate a version string for cache busting for admin pages
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css'; // Correct path from admin folder
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Nova Gaming Admin</title>
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
            <h1>Edit Product</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="edit_product.php?id=<?php echo $product_id; ?>" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($):</label>
                    <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" value="<?php echo $product['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select class="form-control" id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($product['category'] === $cat['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new">Add New Category</option>
                    </select>
                </div>
                
                <div id="new-category-group" class="form-group" style="display: none;">
                    <label for="new-category">New Category Name:</label>
                    <input type="text" class="form-control" id="new-category" name="new_category">
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image:</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <small>Leave empty to keep the current image.</small>
                </div>
                
                <div class="form-group">
                    <label>Current Image:</label>
                    <img src="../assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 200px;">
                </div>
                
                <button type="submit" class="btn">Update Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </form>
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
    <script>
        // Handle category selection
        document.getElementById('category').addEventListener('change', function() {
            const newCategoryGroup = document.getElementById('new-category-group');
            if (this.value === 'new') {
                newCategoryGroup.style.display = 'block';
                document.getElementById('new-category').setAttribute('required', true);
            } else {
                newCategoryGroup.style.display = 'none';
                document.getElementById('new-category').removeAttribute('required');
            }
        });
    </script>
</body>
</html>