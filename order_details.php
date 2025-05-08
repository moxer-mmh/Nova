<?php
// Include the header
include_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Call the stored procedure to get order details
$stmt = $conn->prepare("CALL GetOrderDetails(?)");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if order exists and belongs to the current user
if (empty($order_items) || $order_items[0]['user_id'] != $_SESSION['user_id']) {
    echo "<div class='alert alert-danger'>Order not found or you don't have permission to view it.</div>";
    include_once 'includes/footer.php';
    exit();
}

// Get order summary from the first item
$order_summary = $order_items[0];
?>

<div class="order-details-page">
    <h1>Order Details</h1>
    
    <div class="order-info">
        <h3>Order #<?php echo $order_id; ?></h3>
        <p>Date: <?php echo date('F j, Y, g:i a', strtotime($order_summary['order_date'])); ?></p>
        <p>Status: 
            <span class="status-<?php echo strtolower($order_summary['status']); ?>">
                <?php echo $order_summary['status']; ?>
            </span>
        </p>
    </div>
    
    <div class="order-items">
        <h3>Items Ordered</h3>
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
                        <td>$<?php echo number_format($item['price_per_unit'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['item_total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th>$<?php echo number_format($order_summary['total_amount'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="order-actions">
        <a href="orders.php" class="btn">Back to Orders</a>
        <?php if ($order_summary['status'] === 'Pending'): ?>
            <form method="post" action="cancel_order.php" style="display:inline;">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" name="cancel_order" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel Order</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
// Include the footer
include_once 'includes/footer.php';
?>