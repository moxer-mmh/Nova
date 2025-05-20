<?php
include_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

$stmt = $conn->prepare("CALL GetOrderDetails(?)");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($order_items) || $order_items[0]['user_id'] != $_SESSION['user_id']) {
    echo "<div class='alert alert-danger'>Order not found or you don't have permission to view it.</div>";
    include_once 'includes/footer.php';
    exit();
}

$order_summary = $order_items[0];
?>

<div class="order-confirmation">
    <div class="alert alert-success">
        <h2>Thank you for your order!</h2>
        <p>Your order has been placed successfully and is now being processed.</p>
    </div>
    
    <div class="order-info">
        <h3>Order #<?php echo $order_id; ?></h3>
        <p>Date: <?php echo date('F j, Y, g:i a', strtotime($order_summary['order_date'])); ?></p>
        <p>Status: <?php echo $order_summary['status']; ?></p>
    </div>
    
    <div class="order-details">
        <h3>Order Details</h3>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo number_format($item['price_per_unit'], 2); ?> DA</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['item_total'], 2); ?> DA</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th><?php echo number_format($order_summary['total_amount'], 2); ?> DA</th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="order-actions">
        <a href="orders.php" class="btn">View All Orders</a>
        <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
    </div>
</div>

<?php
include_once 'includes/footer.php';
?>