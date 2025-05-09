<?php
include_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price 
        FROM Cart c 
        JOIN Products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $_SESSION['error'] = "Your cart is empty.";
        header("Location: cart.php");
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("CALL FinalizeOrder(?, @order_id)");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        
        $result = $conn->query("SELECT @order_id as order_id");
        $order_id = $result->fetch_assoc()['order_id'];
        
        if (!$order_id) {
            throw new Exception("Failed to create order.");
        }
        
        $conn->commit();
        
        $_SESSION['success'] = "Order placed successfully!";
        header("Location: order_confirmation.php?id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error processing your order: " . $e->getMessage();
    }
}
?>

<h1>Checkout</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <p>Your cart is empty. You cannot proceed to checkout.</p>
        <a href="products.php" class="btn">Continue Shopping</a>
    </div>
<?php else: ?>
    <div class="checkout-container">
        <div class="order-summary">
            <h2>Order Summary</h2>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo formatCurrency($item['price']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Subtotal</td>
                        <td><?php echo formatCurrency($cart_total); ?></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td><?php echo formatCurrency($cart_total); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="checkout-form">
            <h2>Complete Your Order</h2>
            <form method="post" action="checkout.php">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="address">Shipping Address:</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Payment Method:</label>
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="cash" checked> Cash on Delivery
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="place_order" class="btn">Place Order</button>
                    <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
include_once 'includes/footer.php';
?>