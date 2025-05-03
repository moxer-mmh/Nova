<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/models/Order.php';

// Check if user is admin
if (!Session::get('is_admin')) {
    redirect('/pages/login.php');
    exit;
}

// Initialize order model
$orderModel = new Order();

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get orders with pagination
$orders = $orderModel->getAllOrders($limit, $offset);
$totalOrders = $orderModel->getTotalOrdersCount();
$totalPages = ceil($totalOrders / $limit);

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $result = $orderModel->updateOrderStatus($orderId, $status);
    $statusMessage = $result ? 'Statut mis à jour avec succès.' : 'Erreur lors de la mise à jour du statut.';
}

// Set page title
$pageTitle = 'Gérer les commandes';

// Include header
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<main class="container">
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../backend/includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <h1>Gestion des commandes</h1>
            
            <?php if (isset($statusMessage)): ?>
                <div class="alert <?php echo $result ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $statusMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="alert alert-info">Aucune commande trouvée.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['ORDER_ID']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['USERNAME']); ?><br>
                                    <small><?php echo htmlspecialchars($order['EMAIL']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ORDER_DATE'])); ?></td>
                                <td><?php echo formatPrice($order['TOTAL_AMOUNT']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($order['STATUS']); ?>">
                                        <?php echo ucfirst($order['STATUS']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="/Nova/pages/admin/order-details.php?id=<?php echo $order['ORDER_ID']; ?>" class="btn btn-primary btn-sm">Détails</a>
                                        
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier le statut ?')">
                                            <input type="hidden" name="order_id" value="<?php echo $order['ORDER_ID']; ?>">
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="pending" <?php echo $order['STATUS'] == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                                <option value="processing" <?php echo $order['STATUS'] == 'processing' ? 'selected' : ''; ?>>En traitement</option>
                                                <option value="completed" <?php echo $order['STATUS'] == 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                                <option value="cancelled" <?php echo $order['STATUS'] == 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm">Mettre à jour</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">&laquo; Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Suivant &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../backend/includes/footer.php'; ?>
