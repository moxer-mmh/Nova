<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
include_once '../includes/db.php';

// Handle admin status toggle
if(isset($_POST['toggle_admin'])) {
    $user_id = $_POST['user_id'];
    $is_admin = $_POST['make_admin'] ? 1 : 0;
    
    // Don't allow self-demotion
    if($user_id == $_SESSION['user_id'] && $is_admin == 0) {
        $error = "You cannot remove your own admin status.";
    } else {
        $stmt = $conn->prepare("UPDATE Users SET is_admin = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $is_admin, $user_id);
        $stmt->execute();
        $stmt->close();
        
        header('Location: manage_users.php?updated=1');
        exit();
    }
}

// Set up filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the WHERE clause
$where_clause = "1=1";
$params = [];
$types = '';

if($role_filter === 'admin') {
    $where_clause .= " AND is_admin = 1";
} else if($role_filter === 'customer') {
    $where_clause .= " AND is_admin = 0";
}

if(!empty($search)) {
    $where_clause .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Set up pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($current_page - 1) * $per_page;

// Get total user count with filters
$count_sql = "SELECT COUNT(*) as total FROM Users WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if(!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_users / $per_page);

// Get users with pagination and filters
$sql = "SELECT * FROM Users WHERE $where_clause ORDER BY username ASC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Manage Users - Nova Gaming Admin</title>
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
                    <li><a href="manage_users.php" class="active">Users</a></li>
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
                <li><a href="manage_orders.php">Manage Orders</a></li>
                <li><a href="manage_users.php" class="active">Manage Users</a></li>
            </ul>
        </aside>
        
        <main class="admin-content">
            <h1>Manage Users</h1>
            
            <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success">User role has been updated successfully.</div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="filters">
                <form action="" method="get" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="role">Filter by Role:</label>
                            <select id="role" name="role" class="form-control">
                                <option value="">All Users</option>
                                <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Admins</option>
                                <option value="customer" <?php echo ($role_filter === 'customer') ? 'selected' : ''; ?>>Customers</option>
                            </select>
                        </div>
                        
                        <div class="filter-item search-filter">
                            <label for="search">Search:</label>
                            <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or username">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-sm">Apply Filters</button>
                            <a href="manage_users.php" class="btn btn-sm btn-secondary">Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="user-stats">
                <p>Total Users: <?php echo $total_users; ?></p>
            </div>
            
            <?php if(empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if($user['is_admin']): ?>
                                        <span class="role-badge admin">Admin</span>
                                    <?php else: ?>
                                        <span class="role-badge">Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form action="" method="post" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <?php if($user['is_admin']): ?>
                                            <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                                <input type="hidden" name="make_admin" value="0">
                                                <button type="submit" name="toggle_admin" class="btn btn-sm btn-danger">Remove Admin</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-disabled">Current User</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <input type="hidden" name="make_admin" value="1">
                                            <button type="submit" name="toggle_admin" class="btn btn-sm">Make Admin</button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <a href="view_user_orders.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm">View Orders</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $current_page): ?>
                                <span class="page-link current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Next &raquo;</a>
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
    
    <style>
        .inline-form {
            display: inline-block;
            margin-right: 5px;
        }
        .role-badge {
            background-color: #495057;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .role-badge.admin {
            background-color: #00A8FF;
        }
        .search-filter {
            flex: 2;
        }
        .user-stats {
            margin-bottom: 1rem;
            color: #aaa;
        }
    </style>
    
    <script>
        // Auto-submit role filter when changed
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('role').addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        });
    </script>
</body>
</html>