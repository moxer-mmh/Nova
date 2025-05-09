<?php
include_once 'includes/header.php';

$featured_query = $conn->query("SELECT * FROM Products WHERE featured = 1 ORDER BY created_at DESC LIMIT 6");
$featured_products = $featured_query->fetch_all(MYSQLI_ASSOC);

$categories_query = $conn->query("SELECT DISTINCT category, COUNT(*) as product_count FROM Products GROUP BY category ORDER BY category");
$categories = $categories_query->fetch_all(MYSQLI_ASSOC);

$new_arrivals_query = $conn->query("SELECT * FROM Products ORDER BY created_at DESC LIMIT 4");
$new_arrivals = $new_arrivals_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Welcome to Nova Gaming</h1>
        <p>Your one-stop shop for premium gaming equipment</p>
        <a href="products.php" class="btn btn-large">Shop Now</a>
    </div>
</div>

<section class="featured-section">
    <div class="section-header">
        <h2>Featured Products</h2>
        <a href="products.php?featured=1" class="view-all">View All</a>
    </div>
    
    <div class="product-grid">
        <?php if (count($featured_products) > 0): ?>
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                            <img src="assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        <?php if ($product['featured']): ?>
                            <span class="featured-badge">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <p class="product-price"><?php echo formatCurrency($product['price']); ?></p>
                        <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <div class="product-actions">
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn">View Details</a>
                            <?php if ($product['stock'] > 0): ?>
                                <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-secondary">Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <button disabled class="btn btn-disabled">Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No featured products available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="categories-section">
    <div class="section-header">
        <h2>Shop by Category</h2>
    </div>
    
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <a href="products.php?category=<?php echo urlencode($category['category']); ?>" class="category-card">
                <div class="category-content">
                    <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                    <p><?php echo $category['product_count']; ?> products</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="new-arrivals-section">
    <div class="section-header">
        <h2>New Arrivals</h2>
        <a href="products.php?sort=newest" class="view-all">View All</a>
    </div>
    
    <div class="product-grid">
        <?php foreach ($new_arrivals as $product): ?>
            <div class="product-card">
                <div class="product-img">
                    <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                        <img src="assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <span class="new-badge">New</span>
                </div>
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <p class="product-price"><?php echo formatCurrency($product['price']); ?></p>
                    <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                    <div class="product-actions">
                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="newsletter-section">
    <div class="newsletter-content">
        <h2>Subscribe to Our Newsletter</h2>
        <p>Stay updated with the latest products, exclusive deals, and gaming news.</p>
        <form class="newsletter-form">
            <input type="email" placeholder="Your email address" required>
            <button type="submit" class="btn">Subscribe</button>
        </form>
    </div>
</section>

<?php
include_once 'includes/footer.php';
?>