<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
include_once '../includes/db.php';

// Initialize variables
$name = $description = $price = $stock = $category = '';
$featured = 0;
$errors = [];
$success = false;

// Get categories for dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM Products ORDER BY category ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    // Handle new category
    if ($category === 'new' && !empty($_POST['new_category'])) {
        $category = trim($_POST['new_category']);
    }
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['product_image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed";
        } elseif ($_FILES['product_image']['size'] > $max_size) {
            $errors[] = "Image size should not exceed 5MB";
        } else {
            $image_name = time() . '_' . basename($_FILES['product_image']['name']);
            $upload_dir = '../assets/images/';
            $upload_path = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $image_url = $image_name;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    } else {
        $errors[] = "Product image is required";
    }
    
    // If no errors, save the product
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO Products (name, description, price, stock, category, image_url, featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssdssis", $name, $description, $price, $stock, $category, $image_url, $featured);
        
        if ($stmt->execute()) {
            $success = true;
            // Clear form fields after successful submission
            $name = $description = $price = $stock = $category = '';
            $featured = 0;
        } else {
            $errors[] = "Failed to add product: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Generate a version string for cache busting
$admin_css_file_path = dirname(__DIR__) . '/assets/css/style.css';
$admin_css_version = file_exists($admin_css_file_path) ? filemtime($admin_css_file_path) : '1';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Nova Gaming Admin</title>
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
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="add_product.php" class="active">Add New Product</a></li>
                <li><a href="manage_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h1>Add New Product</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">Product was added successfully!</div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="add_product.php" method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (DA):</label>
                    <input type="number" id="price" name="price" class="form-control" min="0.01" step="0.01" value="<?php echo $price; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" class="form-control" min="0" value="<?php echo $stock; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new">Add New Category</option>
                    </select>
                </div>
                
                <div class="form-group" id="new-category-group" style="display: none;">
                    <label for="new-category">New Category Name:</label>
                    <input type="text" id="new-category" name="new_category" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="product-image">Product Image:</label>
                    <input type="file" id="product-image" name="product_image" class="form-control" accept="image/*" required>
                    <small>Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                        Featured Product
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Add Product</button>
                    <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
                </div>
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
                <p>&copy; <?php echo date('Y'); ?> Nova Gaming. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
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