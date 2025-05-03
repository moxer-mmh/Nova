<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../utils/cookie.php';
require_once __DIR__ . '/../backend/models/Cart.php';
require_once __DIR__ . '/../backend/models/Order.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../backend/config/config.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    // Redirect to login with return URL
    Session::set('return_url', '/Nova/pages/checkout.php');
    redirect('/pages/login.php');
    exit;
}

// Get user data and cart
$userId = Session::get('user_id');
$cart = new Cart();
$userCart = $cart->getCart($userId);

if (!$userCart) {
    redirect('/pages/cart.php');
    exit;
}

$cartId = $userCart['CART_ID'];
$cartItems = $cart->getCartItems($cartId);
$cartTotal = $cart->getCartTotal($cartId);

// Check if cart is empty
if (empty($cartItems)) {
    redirect('/pages/cart.php');
    exit;
}

// Process order submission
$orderPlaced = false;
$orderId = null;
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $shippingCity = trim($_POST['shipping_city'] ?? '');
    $shippingPostalCode = trim($_POST['shipping_postal_code'] ?? '');
    $shippingCountry = trim($_POST['shipping_country'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    // Validate form data
    if (empty($shippingAddress)) {
        $formErrors['shipping_address'] = 'L\'adresse de livraison est requise';
    }
    if (empty($shippingCity)) {
        $formErrors['shipping_city'] = 'La ville est requise';
    }
    if (empty($shippingPostalCode)) {
        $formErrors['shipping_postal_code'] = 'Le code postal est requis';
    }
    if (empty($shippingCountry)) {
        $formErrors['shipping_country'] = 'Le pays est requis';
    }
    if (empty($paymentMethod)) {
        $formErrors['payment_method'] = 'La méthode de paiement est requise';
    }
    
    // If no errors, place order
    if (empty($formErrors)) {
        $orderData = [
            'shipping_address' => $shippingAddress,
            'shipping_city' => $shippingCity,
            'shipping_postal_code' => $shippingPostalCode,
            'shipping_country' => $shippingCountry,
            'payment_method' => $paymentMethod
        ];
        
        $orderObj = new Order();
        $orderId = $orderObj->createOrder($userId, $cartId, $orderData);
        
        if ($orderId) {
            $orderPlaced = true;
            redirect('/pages/order-confirmation.php?id=' . $orderId);
            exit;
        } else {
            $formErrors['general'] = 'Une erreur est survenue lors du traitement de la commande. Veuillez réessayer.';
        }
    }
}

// Set page title
$pageTitle = 'Paiement';

// Load header
require_once __DIR__ . '/../backend/includes/header.php';
?>

<main class="container">
    <h1>Finaliser la commande</h1>
    
    <?php if (!empty($formErrors['general'])): ?>
    <div class="alert alert-danger"><?php echo $formErrors['general']; ?></div>
    <?php endif; ?>
    
    <div class="checkout-layout">
        <div class="checkout-form-container">
            <form method="post" class="checkout-form">
                <h2>Adresse de livraison</h2>
                
                <div class="form-group">
                    <label for="shipping_address">Adresse*</label>
                    <input type="text" id="shipping_address" name="shipping_address" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?>">
                    <?php if (isset($formErrors['shipping_address'])): ?>
                    <div class="form-text text-danger"><?php echo $formErrors['shipping_address']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="shipping_city">Ville*</label>
                        <input type="text" id="shipping_city" name="shipping_city" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['shipping_city'] ?? ''); ?>">
                        <?php if (isset($formErrors['shipping_city'])): ?>
                        <div class="form-text text-danger"><?php echo $formErrors['shipping_city']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_postal_code">Code postal*</label>
                        <input type="text" id="shipping_postal_code" name="shipping_postal_code" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['shipping_postal_code'] ?? ''); ?>">
                        <?php if (isset($formErrors['shipping_postal_code'])): ?>
                        <div class="form-text text-danger"><?php echo $formErrors['shipping_postal_code']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="shipping_country">Pays*</label>
                    <input type="text" id="shipping_country" name="shipping_country" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['shipping_country'] ?? ''); ?>">
                    <?php if (isset($formErrors['shipping_country'])): ?>
                    <div class="form-text text-danger"><?php echo $formErrors['shipping_country']; ?></div>
                    <?php endif; ?>
                </div>
                
                <h2>Paiement</h2>
                
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" id="payment_card" name="payment_method" value="card"
                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'card') ? 'checked' : ''; ?>>
                        <label for="payment_card">Carte bancaire</label>
                    </div>
                    
                    <div class="payment-method">
                        <input type="radio" id="payment_paypal" name="payment_method" value="paypal"
                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'paypal') ? 'checked' : ''; ?>>
                        <label for="payment_paypal">PayPal</label>
                    </div>
                </div>
                <?php if (isset($formErrors['payment_method'])): ?>
                <div class="form-text text-danger"><?php echo $formErrors['payment_method']; ?></div>
                <?php endif; ?>
                
                <div class="checkout-actions">
                    <a href="/Nova/pages/cart.php" class="btn btn-secondary">Retour au panier</a>
                    <button type="submit" class="btn btn-primary">Commander</button>
                </div>
            </form>
        </div>
        
        <div class="checkout-summary">
            <h2>Récapitulatif de la commande</h2>
            
            <div class="checkout-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="checkout-item">
                    <div class="checkout-item-image">
                        <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($item['IMAGE_URL'] ?: 'placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['TITLE']); ?>"
                             onerror="this.onerror=null; this.src='/Nova/frontend/assets/images/placeholder.jpg';">
                    </div>
                    <div>
                        <div class="checkout-item-title"><?php echo htmlspecialchars($item['TITLE']); ?></div>
                        <div>Quantité: <?php echo $item['QUANTITY']; ?></div>
                        <div><?php echo formatPrice($item['PRICE']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="checkout-total">
                <span>Total</span>
                <span><?php echo formatPrice($cartTotal); ?></span>
            </div>
        </div>
    </div>
</main>

<?php
// Load footer
require_once __DIR__ . '/../backend/includes/footer.php';
?>
