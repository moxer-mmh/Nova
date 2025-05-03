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

// Check if order exists and belongs to the user (unless admin)
if (!$orderDetails || ($orderDetails['USER_ID'] != $userId && !Session::get('is_admin'))) {
    redirect('/pages/account.php');
    exit;
}

// Set page title
$pageTitle = 'Détails de la commande #' . $orderId;

// Load header
require_once __DIR__ . '/../backend/includes/header.php';
?>

<main class="container">
    <div class="breadcrumb">
        <a href="/Nova/pages/account.php">Mon compte</a> &gt; 
        <a href="/Nova/pages/account.php?tab=orders">Mes commandes</a> &gt; 
        Commande #<?php echo $orderId; ?>
    </div>
    
    <h1>Détails de la commande #<?php echo $orderId; ?></h1>
    
    <div class="order-details">
        <div class="order-info-card">
            <h2>Informations de commande</h2>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Numéro de commande:</span>
                    <span class="info-value">#<?php echo $orderDetails['ORDER_ID']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($orderDetails['ORDER_DATE'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="info-value status-badge <?php echo strtolower($orderDetails['STATUS']); ?>"><?php echo ucfirst($orderDetails['STATUS']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total:</span>
                    <span class="info-value"><?php echo formatPrice($orderDetails['TOTAL_AMOUNT']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Méthode de paiement:</span>
                    <span class="info-value"><?php echo $orderDetails['PAYMENT_METHOD']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="order-info-card">
            <h2>Adresse de livraison</h2>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_ADDRESS']); ?></p>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_POSTAL_CODE'] . ' ' . $orderDetails['SHIPPING_CITY']); ?></p>
            <p><?php echo htmlspecialchars($orderDetails['SHIPPING_COUNTRY']); ?></p>
        </div>
        
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
                        <div class="order-product">
                            <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($item['IMAGE_URL'] ?: 'placeholder.jpg'); ?>" 
                                 class="order-image" alt="<?php echo htmlspecialchars($item['TITLE']); ?>"
                                 onerror="this.onerror=null; this.src='/Nova/frontend/assets/images/placeholder.jpg';">
                            <div>
                                <div class="order-item-title">
                                    <a href="/Nova/pages/book.php?id=<?php echo $item['BOOK_ID']; ?>"><?php echo htmlspecialchars($item['TITLE']); ?></a>
                                </div>
                                <div class="order-item-author"><?php echo htmlspecialchars($item['AUTHOR']); ?></div>
                            </div>
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
        
        <?php if (Session::get('is_admin')): ?>
        <div class="admin-actions">
            <h2>Actions administrateur</h2>
            <form method="post" action="/Nova/backend/admin/update-order-status.php">
                <input type="hidden" name="order_id" value="<?php echo $orderDetails['ORDER_ID']; ?>">
                <div class="form-group">
                    <label for="status">Changer le statut:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pending" <?php echo ($orderDetails['STATUS'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                        <option value="processing" <?php echo ($orderDetails['STATUS'] == 'processing') ? 'selected' : ''; ?>>En traitement</option>
                        <option value="completed" <?php echo ($orderDetails['STATUS'] == 'completed') ? 'selected' : ''; ?>>Terminée</option>
                        <option value="cancelled" <?php echo ($orderDetails['STATUS'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Load footer
require_once __DIR__ . '/../backend/includes/footer.php';
?>
