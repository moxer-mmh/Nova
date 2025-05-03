<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Cart.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get all orders for admin
    public function getAllOrders($limit = 10, $offset = 0) {
        $sql = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum 
                    FROM (
                        SELECT o.*, u.username, u.email
                        FROM ORDERS o
                        JOIN USERS u ON o.user_id = u.user_id
                        ORDER BY o.order_date DESC
                    ) a
                    WHERE ROWNUM <= :upper_limit
                )
                WHERE rnum > :lower_limit";
                
        $params = [
            ':upper_limit' => $offset + $limit,
            ':lower_limit' => $offset
        ];
        
        $stmt = $this->db->executeQuery($sql, $params);
        return $this->db->fetchAll($stmt);
    }
    
    // Get orders for a specific user
    public function getUserOrders($userId) {
        $sql = "SELECT * FROM ORDERS WHERE user_id = :user_id ORDER BY order_date DESC";
        $stmt = $this->db->executeQuery($sql, [':user_id' => $userId]);
        return $this->db->fetchAll($stmt);
    }
    
    // Get a specific order with details
    public function getOrder($orderId) {
        $sql = "SELECT o.*, u.username, u.email
                FROM ORDERS o
                JOIN USERS u ON o.user_id = u.user_id
                WHERE o.order_id = :order_id";
                
        $stmt = $this->db->executeQuery($sql, [':order_id' => $orderId]);
        $order = $this->db->fetchOne($stmt);
        
        if ($order) {
            // Get order items
            $sql = "SELECT oi.*, b.title, b.author, b.image_url
                    FROM ORDER_ITEMS oi
                    JOIN BOOKS b ON oi.book_id = b.book_id
                    WHERE oi.order_id = :order_id";
                    
            $stmt = $this->db->executeQuery($sql, [':order_id' => $orderId]);
            $items = $this->db->fetchAll($stmt);
            
            $order['items'] = $items;
            return $order;
        }
        
        return false;
    }
    
    // Create a new order from cart
    public function createOrder($userId, $cartId, $orderData) {
        try {
            // Get cart items
            $cart = new Cart();
            $cartItems = $cart->getCartItems($cartId);
            $totalAmount = $cart->getCartTotal($cartId);
            
            if (empty($cartItems)) {
                return false;
            }
            
            // Create the order
            $sql = "INSERT INTO ORDERS (
                order_id,
                user_id,
                total_amount,
                order_date,
                status,
                shipping_address,
                shipping_city,
                shipping_postal_code,
                shipping_country,
                payment_method
            ) VALUES (
                orders_seq.NEXTVAL,
                :user_id,
                :total_amount,
                CURRENT_TIMESTAMP,
                'pending',
                :shipping_address,
                :shipping_city,
                :shipping_postal_code,
                :shipping_country,
                :payment_method
            )";
            
            $params = [
                ':user_id' => $userId,
                ':total_amount' => $totalAmount,
                ':shipping_address' => $orderData['shipping_address'],
                ':shipping_city' => $orderData['shipping_city'],
                ':shipping_postal_code' => $orderData['shipping_postal_code'],
                ':shipping_country' => $orderData['shipping_country'],
                ':payment_method' => $orderData['payment_method']
            ];
            
            $stmt = $this->db->executeQuery($sql, $params);
            
            // Get the new order ID
            $sql = "SELECT orders_seq.CURRVAL as order_id FROM DUAL";
            $idStmt = $this->db->executeQuery($sql);
            $idResult = $this->db->fetchOne($idStmt);
            
            if (!$idResult) {
                return false;
            }
            
            $orderId = $idResult['ORDER_ID'];
            
            // Add order items
            foreach ($cartItems as $item) {
                $sql = "INSERT INTO ORDER_ITEMS (
                    order_item_id,
                    order_id,
                    book_id,
                    quantity,
                    price
                ) VALUES (
                    order_items_seq.NEXTVAL,
                    :order_id,
                    :book_id,
                    :quantity,
                    :price
                )";
                
                $itemParams = [
                    ':order_id' => $orderId,
                    ':book_id' => $item['BOOK_ID'],
                    ':quantity' => $item['QUANTITY'],
                    ':price' => $item['PRICE']
                ];
                
                $this->db->executeQuery($sql, $itemParams);
                
                // Update book stock
                $sql = "UPDATE BOOKS SET stock = stock - :quantity WHERE book_id = :book_id";
                $this->db->executeQuery($sql, [
                    ':book_id' => $item['BOOK_ID'],
                    ':quantity' => $item['QUANTITY']
                ]);
            }
            
            // Clear the cart
            $cart->clearCart($cartId);
            
            return $orderId;
        } catch (Exception $e) {
            error_log('Error creating order: ' . $e->getMessage());
            return false;
        }
    }
    
    // Update order status
    public function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $sql = "UPDATE ORDERS SET status = :status WHERE order_id = :order_id";
        $params = [':order_id' => $orderId, ':status' => $status];
        $stmt = $this->db->executeQuery($sql, $params);
        
        return true;
    }
    
    // Get order count for dashboard
    public function getTotalOrdersCount() {
        $sql = "SELECT COUNT(*) as total FROM ORDERS";
        $stmt = $this->db->executeQuery($sql);
        $result = $this->db->fetchOne($stmt);
        return $result['TOTAL'];
    }
    
    // Get total revenue for dashboard
    public function getTotalRevenue() {
        $sql = "SELECT SUM(total_amount) as total FROM ORDERS WHERE status = 'completed'";
        $stmt = $this->db->executeQuery($sql);
        $result = $this->db->fetchOne($stmt);
        return $result['TOTAL'] ?? 0;
    }
    
    // Get users count for dashboard
    public function getTotalUsersCount() {
        $sql = "SELECT COUNT(*) as total FROM USERS";
        $stmt = $this->db->executeQuery($sql);
        $result = $this->db->fetchOne($stmt);
        return $result['TOTAL'];
    }
}
?>
