<?php
// Include the header
include_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $cart_id => $quantity) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                $delete_stmt = $conn->prepare("DELETE FROM Cart WHERE cart_id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
                $delete_stmt->execute();
            } else {
                // Update quantity
                $update_stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
                $update_stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
                $update_stmt->execute();
            }
        }
        $_SESSION['success'] = "Cart updated successfully.";
        header("Location: cart.php");
        exit();
    } elseif (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
        $cart_id = (int)$_POST['cart_id'];
        $delete_stmt = $conn->prepare("DELETE FROM Cart WHERE cart_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
        $delete_stmt->execute();
        $_SESSION['success'] = "Item removed from cart.";
        header("Location: cart.php");
        exit();
    }
}

// Get cart items
$sql = "SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price, p.image_url, p.stock 
        FROM Cart c 
        JOIN Products p ON c.product_id = p.product_id 
        WHERE c.user_id = ? 
        ORDER BY c.added_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate cart total
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}
?>

<h1>Your Shopping Cart</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <p>Your cart is empty.</p>
        <a href="products.php" class="btn">Continue Shopping</a>
    </div>
<?php else: ?>
    <form method="post" action="cart.php">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td data-label="Product">
                            <div class="cart-product">
                                <img src="assets/images/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" width="80">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <small>SKU: <?php echo $item['product_id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td data-label="Price"><?php echo formatCurrency($item['price']); ?></td>
                        <td data-label="Quantity">
                            <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock']; ?>" class="quantity-input">
                        </td>
                        <td data-label="Total"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        <td data-label="Actions">
                            <form method="post" action="cart.php" style="display:inline;">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="cart-actions">
            <button type="submit" name="update_cart" class="btn">Update Cart</button>
            <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
    </form>
    
    <div class="cart-summary">
        <h3>Cart Summary</h3>
        <table>
            <tr>
                <td>Total Items:</td>
                <td><?php echo count($cart_items); ?></td>
            </tr>
            <tr>
                <td>Subtotal</td>
                <td><?php echo formatCurrency($cart_total); ?></td>
            </tr>
            <tr>
                <td>Total</td>
                <td><?php echo formatCurrency($cart_total); ?></td>
            </tr>
        </table>
        <a href="checkout.php" class="btn">Proceed to Checkout</a>
    </div>
<?php endif; ?>

<?php
// Include the footer
include_once 'includes/footer.php';
?>