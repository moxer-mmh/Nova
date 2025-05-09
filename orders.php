<?php
// Include the header
include_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Call the stored procedure to get order history
$stmt = $conn->prepare("CALL GetOrderHistory(?)");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h1>My Orders</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="empty-orders">
        <p>You haven't placed any orders yet.</p>
        <a href="products.php" class="btn">Start Shopping</a>
    </div>
<?php else: ?>
    <table class="order-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                    <td><?php echo formatCurrency($order['total_amount']); ?></td>
                    <td>
                        <span class="status-<?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View Details</a>
                        <?php if ($order['status'] === 'Pending'): ?>
                            <form method="post" action="cancel_order.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="cancel_order" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
// Include the footer
include_once 'includes/footer.php';
?>