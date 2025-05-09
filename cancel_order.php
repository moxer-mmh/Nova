<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    
    try {
        $conn->begin_transaction();
        
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
        
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Cancelled' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to cancel the order.");
        }
        
        $conn->commit();
        
        $_SESSION['success'] = "Order #$order_id has been cancelled successfully.";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error cancelling order: " . $e->getMessage();
    }
    
    header("Location: orders.php");
    exit();
} else {
    header("Location: orders.php");
    exit();
}
?>