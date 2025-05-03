<?php
require_once __DIR__ . '/../config/database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getCart($userId) {
        if (!$userId) {
            return false;
        }
        
        // Check if user already has a cart
        $sql = "SELECT * FROM CARTS WHERE user_id = :user_id";
        $stmt = $this->db->executeQuery($sql, [':user_id' => $userId]);
        $cart = $this->db->fetchOne($stmt);
        
        if ($cart) {
            return $cart;
        }
        
        // Create new cart for user if none exists
        $cartId = $this->createCart($userId);
        
        $sql = "SELECT * FROM CARTS WHERE cart_id = :cart_id";
        $stmt = $this->db->executeQuery($sql, [':cart_id' => $cartId]);
        return $this->db->fetchOne($stmt);
    }
    
    public function createCart($userId) {
        $sql = "INSERT INTO CARTS (cart_id, user_id, created_at) VALUES (carts_seq.NEXTVAL, :user_id, CURRENT_TIMESTAMP)";
        $params = [':user_id' => $userId];
        
        $stmt = $this->db->executeQuery($sql, $params);
        
        // Get the newly created cart ID
        $sql = "SELECT carts_seq.CURRVAL as cart_id FROM DUAL";
        $idStmt = $this->db->executeQuery($sql);
        $idResult = $this->db->fetchOne($idStmt);
        
        return $idResult ? $idResult['CART_ID'] : false;
    }
    
    public function addToCart($cartId, $bookId, $quantity = 1) {
        // Make sure quantity is a positive integer
        $quantity = max(1, intval($quantity));
        
        try {
            // Check if item already exists in cart
            $sql = "SELECT * FROM CART_ITEMS WHERE cart_id = :cart_id AND book_id = :book_id";
            $params = [':cart_id' => $cartId, ':book_id' => $bookId];
            $stmt = $this->db->executeQuery($sql, $params);
            $existingItem = $this->db->fetchOne($stmt);
            
            if ($existingItem) {
                // Update quantity if item already in cart
                $newQuantity = $existingItem['QUANTITY'] + $quantity;
                return $this->updateCartItem($existingItem['CART_ITEM_ID'], $newQuantity);
            } else {
                // Add new item to cart - removing the ADDED_AT column if it doesn't exist
                $sql = "INSERT INTO CART_ITEMS (cart_item_id, cart_id, book_id, quantity) 
                        VALUES (cart_items_seq.NEXTVAL, :cart_id, :book_id, :quantity)";
                $params = [
                    ':cart_id' => $cartId,
                    ':book_id' => $bookId,
                    ':quantity' => $quantity
                ];
                
                $stmt = $this->db->executeQuery($sql, $params);
                return true;
            }
        } catch (Exception $e) {
            error_log('Error adding item to cart: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateCartItem($itemId, $quantity) {
        // Make sure quantity is a positive integer
        $quantity = max(1, intval($quantity));
        
        $sql = "UPDATE CART_ITEMS SET quantity = :quantity WHERE cart_item_id = :item_id";
        $params = [':item_id' => $itemId, ':quantity' => $quantity];
        $stmt = $this->db->executeQuery($sql, $params);
        return true;
    }
    
    public function removeFromCart($cartId, $itemId) {
        $sql = "DELETE FROM CART_ITEMS WHERE cart_id = :cart_id AND cart_item_id = :item_id";
        $params = [':cart_id' => $cartId, ':item_id' => $itemId];
        $stmt = $this->db->executeQuery($sql, $params);
        return true;
    }
    
    public function getCartItems($cartId) {
        $sql = "SELECT ci.*, b.title, b.author, b.price, b.image_url 
                FROM CART_ITEMS ci 
                JOIN BOOKS b ON ci.book_id = b.book_id 
                WHERE ci.cart_id = :cart_id";
                
        $stmt = $this->db->executeQuery($sql, [':cart_id' => $cartId]);
        return $this->db->fetchAll($stmt);
    }
    
    public function getCartItemsCount($cartId) {
        try {
            if (!$cartId) {
                return 0;
            }
            
            $sql = "SELECT NVL(SUM(quantity), 0) as total FROM CART_ITEMS WHERE cart_id = :cart_id";
            $stmt = $this->db->executeQuery($sql, [':cart_id' => $cartId]);
            $result = $this->db->fetchOne($stmt);
            
            return $result && isset($result['TOTAL']) ? intval($result['TOTAL']) : 0;
        } catch (Exception $e) {
            error_log('Error in getCartItemsCount: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getCartTotal($cartId) {
        $sql = "SELECT SUM(ci.quantity * b.price) as total 
                FROM CART_ITEMS ci 
                JOIN BOOKS b ON ci.book_id = b.book_id 
                WHERE ci.cart_id = :cart_id";
                
        $stmt = $this->db->executeQuery($sql, [':cart_id' => $cartId]);
        $result = $this->db->fetchOne($stmt);
        
        return $result && isset($result['TOTAL']) ? floatval($result['TOTAL']) : 0;
    }
    
    public function getItemSubtotal($itemId) {
        $sql = "SELECT ci.quantity * b.price as subtotal 
                FROM CART_ITEMS ci 
                JOIN BOOKS b ON ci.book_id = b.book_id 
                WHERE ci.cart_item_id = :item_id";
                
        $stmt = $this->db->executeQuery($sql, [':item_id' => $itemId]);
        $result = $this->db->fetchOne($stmt);
        
        return $result && isset($result['SUBTOTAL']) ? floatval($result['SUBTOTAL']) : 0;
    }
    
    public function clearCart($cartId) {
        $sql = "DELETE FROM CART_ITEMS WHERE cart_id = :cart_id";
        $stmt = $this->db->executeQuery($sql, [':cart_id' => $cartId]);
        return true;
    }
}
?>
