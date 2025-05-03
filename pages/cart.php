<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/Cart.php';
require_once __DIR__ . '/../backend/models/Book.php';
require_once __DIR__ . '/../backend/config/config.php';

// Titre de la page
$pageTitle = 'Mon Panier';

// Get cart data
$userId = Session::get('user_id');
$cart = new Cart();

// If user is logged in, get their cart
if ($userId) {
    $userCart = $cart->getCart($userId);
    $cartId = $userCart ? $userCart['CART_ID'] : null;
} else {
    // If not logged in, use cart from cookie
    $cartId = isset($_COOKIE['cart_id']) ? $_COOKIE['cart_id'] : null;
}

// Get cart items
$cartItems = $cartId ? $cart->getCartItems($cartId) : [];
$cartTotal = $cartId ? $cart->getCartTotal($cartId) : 0;

// Load header
require_once __DIR__ . '/../backend/includes/header.php';
?>

<main class="container">
    <h1>Mon Panier</h1>
    
    <div class="cart-container">
        <?php if (empty($cartItems)): ?>
            <div id="cart-empty-message">
                <p>Votre panier est vide.</p>
                <a href="/Nova/pages/books.php" class="btn btn-primary">Parcourir les livres</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-product">
                                <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($item['IMAGE_URL']); ?>" class="cart-image" alt="<?php echo htmlspecialchars($item['TITLE']); ?>">
                                <div>
                                    <div class="cart-item-title">
                                        <a href="/Nova/pages/book.php?id=<?php echo $item['BOOK_ID']; ?>"><?php echo htmlspecialchars($item['TITLE']); ?></a>
                                    </div>
                                    <div class="cart-item-author"><?php echo htmlspecialchars($item['AUTHOR']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['PRICE']); ?></td>
                        <td>
                            <div class="cart-quantity">
                                <button class="quantity-btn decrement-btn" data-id="<?php echo $item['CART_ITEM_ID']; ?>">-</button>
                                <input type="number" class="cart-quantity-input" value="<?php echo $item['QUANTITY']; ?>" min="1" data-id="<?php echo $item['CART_ITEM_ID']; ?>">
                                <button class="quantity-btn increment-btn" data-id="<?php echo $item['CART_ITEM_ID']; ?>">+</button>
                            </div>
                        </td>
                        <td id="item-subtotal-<?php echo $item['CART_ITEM_ID']; ?>"><?php echo formatPrice($item['PRICE'] * $item['QUANTITY']); ?></td>
                        <td>
                            <button class="btn btn-danger remove-from-cart-btn" data-id="<?php echo $item['CART_ITEM_ID']; ?>">Supprimer</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <div class="cart-total">
                    <span>Total</span>
                    <span id="cart-total"><?php echo formatPrice($cartTotal); ?></span>
                </div>
                <div class="cart-actions">
                    <a href="/Nova/pages/books.php" class="btn btn-secondary">Continuer mes achats</a>
                    <a href="/Nova/pages/checkout.php" class="btn btn-primary" id="checkout-btn">Passer à la caisse</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add cart JavaScript -->
<script src="/Nova/frontend/assets/js/cart.js"></script>

<?php
// Load footer
require_once __DIR__ . '/../backend/includes/footer.php';
?>
