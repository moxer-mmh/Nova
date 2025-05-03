<?php
// Set JSON header immediately to ensure all responses are valid JSON
header('Content-Type: application/json');

// Prevent direct HTML error output in API response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Create log directory if it doesn't exist
$logDir = __DIR__ . '/../../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Custom error handler to log errors instead of displaying them
function errorHandler($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/../../logs/cart_errors.log';
    $message = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($message, 3, $logFile);
    return true; // Don't execute PHP's internal error handler
}
set_error_handler('errorHandler');

try {
    require_once __DIR__ . '/../../utils/session.php';
    require_once __DIR__ . '/../../utils/cookie.php';
    require_once __DIR__ . '/../models/Cart.php';
    require_once __DIR__ . '/../models/Book.php';
    
    // Get the cart ID
    $userId = Session::get('user_id');
    $cart = new Cart();
    
    // If user is logged in, get their cart
    if ($userId) {
        $userCart = $cart->getCart($userId);
        $cartId = $userCart ? $userCart['CART_ID'] : false;
        
        // If no cart found, create one
        if (!$cartId) {
            $cartId = $cart->createCart($userId);
        }
    } else {
        // If not logged in, use anonymous cart
        $cartId = isset($_COOKIE['cart_id']) ? $_COOKIE['cart_id'] : false;
        if (!$cartId) {
            $cartId = $cart->createCart(null);
            setcookie('cart_id', $cartId, time() + 30 * 24 * 60 * 60, '/'); // 30 days
        }
    }
    
    if (!$cartId) {
        throw new Exception('Failed to get or create cart');
    }
    
    $action = isset($_POST['action']) ? $_POST['action'] : 
              (isset($_GET['action']) ? $_GET['action'] : '');
    
    $response = [];
    
    switch ($action) {
        case 'add':
            // Process adding item to cart
            $bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            if ($bookId <= 0) {
                throw new Exception('Invalid book ID');
            }
            
            $result = $cart->addToCart($cartId, $bookId, $quantity);
            if ($result) {
                $count = $cart->getCartItemsCount($cartId);
                $response = [
                    'success' => true,
                    'message' => 'Livre ajouté au panier',
                    'count' => $count
                ];
            } else {
                throw new Exception('Failed to add item to cart');
            }
            break;
            
        case 'update':
            // Process updating item quantity
            $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            
            if ($itemId > 0 && $quantity > 0) {
                $result = $cart->updateCartItem($itemId, $quantity);
                if ($result) {
                    $itemSubtotal = $cart->getItemSubtotal($itemId);
                    $total = $cart->getCartTotal($cartId);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Panier mis à jour',
                        'item_subtotal' => $itemSubtotal,
                        'total' => $total
                    ];
                } else {
                    throw new Exception('Failed to update cart item');
                }
            } else {
                throw new Exception('Invalid parameters');
            }
            break;
            
        case 'remove':
            // Process removing item from cart
            $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
            
            if ($itemId > 0) {
                $result = $cart->removeFromCart($cartId, $itemId);
                if ($result) {
                    $total = $cart->getCartTotal($cartId);
                    $count = $cart->getCartItemsCount($cartId);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Article supprimé du panier',
                        'total' => $total,
                        'count' => $count
                    ];
                } else {
                    throw new Exception('Failed to remove item from cart');
                }
            } else {
                throw new Exception('Invalid item ID');
            }
            break;
            
        case 'count':
            // Get cart items count
            $count = $cart->getCartItemsCount($cartId);
            $response = [
                'success' => true,
                'count' => $count
            ];
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Action non valide'
            ];
    }
} catch (Exception $e) {
    // Log the error
    error_log($e->getMessage() . "\n" . $e->getTraceAsString(), 3, __DIR__ . '/../../logs/cart_errors.log');
    
    // Return error response
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

// Always send a valid JSON response
echo json_encode($response);
exit;
