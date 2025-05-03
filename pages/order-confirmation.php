<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/Order.php';
require_once __DIR__ . '/../backend/config/config.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    redirect('/pages/login.php');
    exit;
}

// Get order ID from URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = Session::get('user_id');

if ($orderId <= 0) {
    redirect('/pages/account.php');
    exit;
}

// Get order details
$order = new Order();
$orderDetails = $order->getOrder($orderId);

// Check if order exists and belongs to the user
if (!$orderDetails || $orderDetails['USER_ID'] != $userId) {
    redirect('/pages/account.php');
    exit;
}

// Set page title
$pageTitle = 'Confirmation de commande';

// Load header
require_once __DIR__ . '/../backend/includes/header.php';
?>

<main class="container order-confirmation">
    <div class="confirmation-header">
        <div class="icon-success">✓</div>
        <h1>Merci pour votre commande!</h1>
        <p>Votre commande a été confirmée et sera bientôt traitée.</p>
        <p>Le numéro de votre commande est <strong>#<?php echo $orderId; ?></strong>.</p>
    </div>
    
    <div class="order-summary">
        <div class="order-info">
            <h2>Informations de commande</h2>
            <ul>
                <li><strong>Numéro de commande:</strong> #<?php echo $orderDetails['ORDER_ID']; ?></li>
                <li><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($orderDetails['ORDER_DATE'])); ?></li>
                <li><strong>Total:</strong> <?php echo formatPrice($orderDetails['TOTAL_AMOUNT']); ?></li>
                <li><strong>Statut:</strong> <span class="order-status"><?php echo ucfirst($orderDetails['STATUS']); ?></span></li>
            </ul>
        </div>
        
        <div class="shipping-info">
            <h2>Adresse de livraison</h2>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_ADDRESS']); ?></p>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_POSTAL_CODE'] . ' ' . $orderDetails['SHIPPING_CITY']); ?></p>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_COUNTRY']); ?></p>
        </div>
    </div>
    
    <div class="order-items">
        <h2>Articles commandés</h2>
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderDetails['items'] as $item): ?>
                <tr>
                    <td>
                        <div class="cart-product">
                            <?php echo htmlspecialchars($item['TITLE']); ?><br>
                            <small><?php echo htmlspecialchars($item['AUTHOR']); ?></small>
                        </div>
                    </td>
                    <td><?php echo formatPrice($item['PRICE']); ?></td>
                    <td><?php echo $item['QUANTITY']; ?></td>
                    <td><?php echo formatPrice($item['PRICE'] * $item['QUANTITY']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="total-label">Total</td>
                    <td><?php echo formatPrice($orderDetails['TOTAL_AMOUNT']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="confirmation-actions">
        <a href="/Nova/pages/account.php?tab=orders" class="btn btn-secondary">Voir mes commandes</a>
        <a href="/Nova/pages/books.php" class="btn btn-primary">Continuer mes achats</a>
    </div>
</main>

<?php
// Load footer
require_once __DIR__ . '/../backend/includes/footer.php';
?>
