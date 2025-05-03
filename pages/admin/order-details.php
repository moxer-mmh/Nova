<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/models/Order.php';

// Check if user is admin
if (!Session::get('is_admin')) {
    redirect('/pages/login.php');
    exit;
}

// Get order ID from URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    redirect('/pages/admin/orders.php');
    exit;
}

// Initialize order model
$orderModel = new Order();
$order = $orderModel->getOrder($orderId);

if (!$order) {
    redirect('/pages/admin/orders.php');
    exit;
}

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $result = $orderModel->updateOrderStatus($orderId, $status);
    $statusMessage = $result ? 'Statut mis à jour avec succès.' : 'Erreur lors de la mise à jour du statut.';
}

// Set page title
$pageTitle = 'Détails de commande #' . $orderId;

// Include header
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<main class="container">
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../backend/includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Détails de commande #<?php echo $orderId; ?></h1>
                <div>
                    <a href="/Nova/pages/admin/orders.php" class="btn btn-secondary">Retour aux commandes</a>
                </div>
            </div>
            
            <?php if (isset($statusMessage)): ?>
                <div class="alert <?php echo $result ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $statusMessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-details">
                <div class="order-info-grid">
                    <div class="order-info-card">
                        <h2>Informations de commande</h2>
                        <table class="info-table">
                            <tr>
                                <th>Numéro de commande:</th>
                                <td>#<?php echo $order['ORDER_ID']; ?></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ORDER_DATE'])); ?></td>
                            </tr>
                            <tr>
                                <th>Total:</th>
                                <td><?php echo formatPrice($order['TOTAL_AMOUNT']); ?></td>
                            </tr>
                            <tr>
                                <th>Statut:</th>
                                <td>
                                    <span class="status-badge <?php echo strtolower($order['STATUS']); ?>">
                                        <?php echo ucfirst($order['STATUS']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Méthode de paiement:</th>
                                <td><?php echo htmlspecialchars($order['PAYMENT_METHOD']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="order-info-card">
                        <h2>Informations client</h2>
                        <table class="info-table">
                            <tr>
                                <th>Nom d'utilisateur:</th>
                                <td><?php echo htmlspecialchars($order['USERNAME']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($order['EMAIL']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="order-info-card">
                        <h2>Adresse de livraison</h2>
                        <p><?php echo htmlspecialchars($order['SHIPPING_ADDRESS']); ?></p>
                        <p><?php echo htmlspecialchars($order['SHIPPING_POSTAL_CODE'] . ' ' . $order['SHIPPING_CITY']); ?></p>
                        <p><?php echo htmlspecialchars($order['SHIPPING_COUNTRY']); ?></p>
                    </div>
                    
                    <div class="order-info-card">
                        <h2>Actions</h2>
                        <form method="post">
                            <div class="form-group">
                                <label for="status">Changer le statut:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?php echo ($order['STATUS'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                    <option value="processing" <?php echo ($order['STATUS'] == 'processing') ? 'selected' : ''; ?>>En traitement</option>
                                    <option value="completed" <?php echo ($order['STATUS'] == 'completed') ? 'selected' : ''; ?>>Terminée</option>
                                    <option value="cancelled" <?php echo ($order['STATUS'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
                
                <h2>Articles commandés</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="order-product">
                                        <img src="/Nova/frontend/assets/images/books/<?php echo htmlspecialchars($item['IMAGE_URL'] ?: 'placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['TITLE']); ?>" 
                                             class="order-image"
                                             onerror="this.src='/Nova/frontend/assets/images/books/placeholder.jpg'">
                                        <div>
                                            <div class="order-item-title"><?php echo htmlspecialchars($item['TITLE']); ?></div>
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
                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                            <td><strong><?php echo formatPrice($order['TOTAL_AMOUNT']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../backend/includes/footer.php'; ?>
