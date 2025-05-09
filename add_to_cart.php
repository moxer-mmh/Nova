<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = "Invalid product or quantity.";
        header("Location: products.php");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT stock FROM Products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found.");
        }
        
        $product = $result->fetch_assoc();
        
        if ($product['stock'] < $quantity) {
            throw new Exception("Not enough stock available.");
        }
        
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM Cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $cart_item = $result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($new_quantity > $product['stock']) {
                throw new Exception("Cannot add more items than available in stock.");
            }
            
            $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO Cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "Item added to cart successfully.";
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.php';
    header("Location: $redirect");
    exit();
} else {
    header("Location: products.php");
    exit();
}
?>