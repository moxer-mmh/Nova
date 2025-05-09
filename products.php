<?php
include_once 'includes/header.php';

$category = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name_asc';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 12;

$where_clauses = ["1=1"];
$params = [];
$types = '';

if (!empty($category)) {
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($min_price > 0) {
    $where_clauses[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price < 10000) {
    $where_clauses[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$where_clause = implode(' AND ', $where_clauses);

$sort_clause = "";
switch ($sort_by) {
    case 'price_asc':
        $sort_clause = "price ASC";
        break;
    case 'price_desc':
        $sort_clause = "price DESC";
        break;
    case 'name_desc':
        $sort_clause = "name DESC";
        break;
    case 'newest':
        $sort_clause = "created_at DESC";
        break;
    default:
        $sort_clause = "name ASC";
        break;
}

$count_sql = "SELECT COUNT(*) as total FROM Products WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_products = $total_row['total'];
$stmt->close();

$total_pages = ceil($total_products / $products_per_page);
$offset = ($page - 1) * $products_per_page;

$cat_result = $conn->query("SELECT DISTINCT category FROM Products ORDER BY category");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT * FROM Products WHERE $where_clause ORDER BY $sort_clause LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $products_per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="products-page-wrapper">
    <aside class="products-sidebar">
        <div class="filter-toggle">
            <h3>Filter Products</h3>
            <span class="filter-toggle-icon">▼</span>
        </div>
        <div class="filter-content">
            <form action="products.php" method="get" id="filter-form">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                
                <div class="filter-group">
                    <h4>Categories</h4>
                    <select name="category" class="form-control" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <h4>Price Range</h4>
                    <div class="price-slider">
                        <input type="number" name="min_price" min="0" max="10000" step="10" 
                            value="<?php echo $min_price; ?>" class="form-control price-input">
                        <span>to</span>
                        <input type="number" name="max_price" min="0" max="10000" step="10" 
                            value="<?php echo $max_price; ?>" class="form-control price-input">
                    </div>
                    <button type="submit" class="btn btn-sm">Apply Price Filter</button>
                </div>
                
                <div class="filter-group">
                    <h4>Sort By</h4>
                    <select name="sort_by" class="form-control" onchange="this.form.submit()">
                        <option value="name_asc" <?php echo ($sort_by === 'name_asc') ? 'selected' : ''; ?>>
                            Name (A to Z)
                        </option>
                        <option value="name_desc" <?php echo ($sort_by === 'name_desc') ? 'selected' : ''; ?>>
                            Name (Z to A)
                        </option>
                        <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>
                            Price (Low to High)
                        </option>
                        <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>
                            Price (High to Low)
                        </option>
                        <option value="newest" <?php echo ($sort_by === 'newest') ? 'selected' : ''; ?>>
                            Newest First
                        </option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset Filters</button>
                </div>
            </form>
        </div>
    </aside>

    <main class="products-content">
        <div class="products-header">
            <h1>
                <?php if (!empty($category)): ?>
                    <?php echo htmlspecialchars($category); ?> Products
                <?php elseif (!empty($search)): ?>
                    Search Results for "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    All Products
                <?php endif; ?>
            </h1>
            
            <div class="products-summary">
                Showing <?php echo min($total_products, $products_per_page); ?> of <?php echo $total_products; ?> products
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="no-products-message">
                <p>No products found matching your criteria.</p>
                <p><a href="products.php" class="btn">View All Products</a></p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                <img src="assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <p class="product-price"><?php echo formatCurrency($product['price']); ?></p>
                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-secondary">View Details</a>
                                <?php if ($product['stock'] > 0): ?>
                                    <form action="add_to_cart.php" method="post" class="add-to-cart-mini">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="btn btn-disabled">Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination"></div>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo ($min_price > 0) ? '&min_price=' . $min_price : ''; ?><?php echo ($max_price < 10000) ? '&max_price=' . $max_price : ''; ?><?php echo !empty($sort_by) ? '&sort_by=' . urlencode($sort_by) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo ($min_price > 0) ? '&min_price=' . $min_price : ''; ?><?php echo ($max_price < 10000) ? '&max_price=' . $max_price : ''; ?><?php echo !empty($sort_by) ? '&sort_by=' . urlencode($sort_by) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo ($min_price > 0) ? '&min_price=' . $min_price : ''; ?><?php echo ($max_price < 10000) ? '&max_price=' . $max_price : ''; ?><?php echo !empty($sort_by) ? '&sort_by=' . urlencode($sort_by) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script>
function resetFilters() {
    const searchParam = new URLSearchParams(window.location.search).get('search');
    if (searchParam) {
        window.location.href = 'products.php?search=' + encodeURIComponent(searchParam);
    } else {
        window.location.href = 'products.php';
    }
}
</script>

<?php
include_once 'includes/footer.php';
?>