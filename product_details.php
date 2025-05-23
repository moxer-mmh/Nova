<?php
include_once 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];

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
            <span class="product-category">Category: <?php echo htmlspecialchars($product['category']); ?></span>
            <span class="product-sku">SKU: <?php echo $product['product_id']; ?></span>
        </div>
                
        <p class="product-price"><?php echo formatCurrency($product['price']); ?></p>
        
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

<script>
function changeMainImage(src) {
    const mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.src = src;
        
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => {
            if (thumb.src === src) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }
}

function decrementQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
        quantityInput.dispatchEvent(new Event('change'));
    }
}

function incrementQuantity(maxStock) {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    if (currentValue < maxStock) {
        quantityInput.value = currentValue + 1;
        quantityInput.dispatchEvent(new Event('change'));
    } else {
        quantityInput.classList.add('max-reached');
        setTimeout(() => {
            quantityInput.classList.remove('max-reached');
        }, 500);
    }
}

function notifyWhenAvailable(productId) {
    const modal = document.createElement('div');
    modal.className = 'notification-modal';
    modal.innerHTML = `
        <div class="notification-content">
            <h3>Get Notified</h3>
            <p>We'll email you when this product is back in stock.</p>
            <input type="email" id="notification-email" placeholder="Your email address" required>
            <div class="notification-actions">
                <button onclick="submitNotification(${productId})" class="btn">Notify Me</button>
                <button onclick="closeNotificationModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.getElementById('notification-email').focus();
}

function submitNotification(productId) {
    const email = document.getElementById('notification-email').value;
    if (!email || !email.includes('@')) {
        alert('Please enter a valid email address');
        return;
    }
    
    alert('Thank you! We will notify you when this product is back in stock.');
    closeNotificationModal();
    
}

function closeNotificationModal() {
    const modal = document.querySelector('.notification-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

document.head.insertAdjacentHTML('beforeend', `
<style>
    .max-reached {
        background-color: #ffecec;
        transition: background-color 0.3s;
    }
    .notification-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .notification-content {
        background-color: #1f1f1f;
        border-radius: 8px;
        padding: 2rem;
        width: 400px;
        max-width: 90%;
    }
    .notification-actions {
        margin-top: 1.5rem;
        display: flex;
        gap: 1rem;
    }
    #notification-email {
        width: 100%;
        padding: 0.75rem;
        margin-top: 1rem;
        border: 1px solid #333;
        background-color: #2a2a2a;
        color: #e0e0e0;
        border-radius: 4px;
    }
</style>
`);
</script>

<?php
include_once 'includes/footer.php';
?>