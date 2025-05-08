<?php
// Include the header
include_once 'includes/header.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];

// Get product details
$stmt = $conn->prepare("SELECT * FROM Products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found.";
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Get related products (same category, excluding current product)
$stmt = $conn->prepare("SELECT * FROM Products WHERE category = ? AND product_id != ? LIMIT 4");
$stmt->bind_param("si", $product['category'], $product_id);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="breadcrumb">
    <a href="index.php">Home</a> &gt;
    <a href="products.php">Products</a> &gt;
    <a href="products.php?category=<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars($product['category']); ?></a> &gt;
    <span><?php echo htmlspecialchars($product['name']); ?></span>
</div>

<div class="product-details">
    <div class="product-gallery">
        <div class="main-image">
            <img src="assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
        </div>
        <?php if (!empty($product['gallery_images'])): ?>
            <div class="thumbnail-images">
                <?php 
                $gallery_images = explode(',', $product['gallery_images']);
                foreach ($gallery_images as $image): 
                ?>
                    <img src="assets/images/<?php echo htmlspecialchars(trim($image)); ?>" alt="Product thumbnail" class="thumbnail" onclick="changeMainImage(this.src)">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="product-info">
        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="product-meta">
            <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
            <span class="product-sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
        </div>
        
        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
        
        <div class="product-stock">
            <?php if ($product['stock'] > 0): ?>
                <span class="in-stock">In Stock (<?php echo $product['stock']; ?> available)</span>
            <?php else: ?>
                <span class="out-of-stock">Out of Stock</span>
            <?php endif; ?>
        </div>
        
        <div class="product-description">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>
        
        <?php if (!empty($product['specifications'])): ?>
            <div class="product-specifications">
                <h3>Specifications</h3>
                <ul>
                    <?php 
                    $specifications = json_decode($product['specifications'], true);
                    foreach ($specifications as $key => $value): 
                    ?>
                        <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($product['stock'] > 0): ?>
            <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div class="quantity-selector">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-buttons">
                        <button type="button" onclick="decrementQuantity()" class="quantity-btn">-</button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button type="button" onclick="incrementQuantity(<?php echo $product['stock']; ?>)" class="quantity-btn">+</button>
                    </div>
                </div>
                
                <div class="product-actions">
                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </div>
            </form>
        <?php else: ?>
            <div class="product-actions">
                <button disabled class="btn btn-disabled">Out of Stock</button>
                <button class="btn btn-secondary" onclick="notifyWhenAvailable(<?php echo $product['product_id']; ?>)">Notify When Available</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (count($related_products) > 0): ?>
    <section class="related-products-section">
        <h2>Related Products</h2>
        
        <div class="product-grid">
            <?php foreach ($related_products as $related): ?>
                <div class="product-card">
                    <div class="product-img">
                        <a href="product_details.php?id=<?php echo $related['product_id']; ?>">
                            <img src="assets/images/<?php echo htmlspecialchars($related['image_url']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                        </a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product_details.php?id=<?php echo $related['product_id']; ?>">
                                <?php echo htmlspecialchars($related['name']); ?>
                            </a>
                        </h3>
                        <p class="product-price">$<?php echo number_format($related['price'], 2); ?></p>
                        <div class="product-actions">
                            <a href="product_details.php?id=<?php echo $related['product_id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<script></script>
function changeMainImage(src) {
    document.getElementById('main-product-image').src = src;
}

function decrementQuantity() {
    var quantityInput = document.getElementById('quantity');
    var currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

function incrementQuantity(maxStock) {
    var quantityInput = document.getElementById('quantity');
    var currentValue = parseInt(quantityInput.value);
    if (currentValue < maxStock) {
        quantityInput.value = currentValue + 1;
    }
}

function notifyWhenAvailable(productId) {
    alert('This feature is coming soon!');
    // In a real implementation, you would collect the user's email and store it with the product ID
}
</script>

<?php
// Include the footer
include_once 'includes/footer.php';
?>