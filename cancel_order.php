<?php
// Include database connection
require_once 'includes/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, check if the order belongs to the current user and is in 'Pending' status
        $stmt = $conn->prepare("SELECT status FROM Orders WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Order not found or you don't have permission to cancel it.");
        }
        
        $order = $result->fetch_assoc();
        
        if ($order['status'] !== 'Pending') {
            throw new Exception("Only pending orders can be cancelled.");
        }
        
        // Update order status to 'Cancelled' - this will trigger the cancel order trigger
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Cancelled' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to cancel the order.");
        }
        
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['success'] = "Order #$order_id has been cancelled successfully.";
        
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        $_SESSION['error'] = "Error cancelling order: " . $e->getMessage();
    }
    
    // Redirect back to orders page
    header("Location: orders.php");
    exit();
} else {
    // If not a POST request, redirect to orders page
    header("Location: orders.php");
    exit();
}
?>