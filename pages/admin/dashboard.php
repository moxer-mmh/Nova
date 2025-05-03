<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../backend/models/Book.php';
require_once __DIR__ . '/../../backend/models/Order.php';

// Vérifier que l'utilisateur est administrateur
Session::requireAdmin();

// Titre de la page
$pageTitle = 'Tableau de bord administration';

// Récupérer des statistiques
$book = new Book();
$order = new Order();

// Commandes récentes
$recentOrders = $order->getAllOrders(5);

// Inclusion de l'en-tête
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <ul class="admin-menu">
            <li class="active"><a href="/Nova/pages/admin/dashboard.php">Tableau de bord</a></li>
            <li><a href="/Nova/pages/admin/books.php">Gérer les livres</a></li>
            <li><a href="/Nova/pages/admin/orders.php">Gérer les commandes</a></li>
            <li><a href="/Nova/pages/admin/users.php">Gérer les utilisateurs</a></li>
        </ul>
    </div>
    
    <div class="admin-content">
        <h1>Tableau de bord</h1>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-value">
                        <?php echo $book->getTotalBooksCount(); ?>
                    </div>
                    <div class="stat-label">Livres</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div class="stat-info">
                    <div class="stat-value">
                        <?php echo $order->getTotalOrdersCount(); ?>
                    </div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <div class="stat-value">
                        <?php echo $order->getTotalUsersCount(); ?>
                    </div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <div class="stat-value">
                        <?php echo formatPrice($order->getTotalRevenue()); ?>
                    </div>
                    <div class="stat-label">Revenus</div>
                </div>
            </div>
        </div>
        
        <div class="admin-recent">
            <h2>Commandes récentes</h2>
            
            <?php if (empty($recentOrders)): ?>
                <p>Aucune commande récente.</p>
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
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo $order['ORDER_ID']; ?></td>
                                <td><?php echo htmlspecialchars($order['USERNAME']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ORDER_DATE'])); ?></td>
                                <td><?php echo formatPrice($order['TOTAL_AMOUNT']); ?></td>
                                <td><span class="status-badge <?php echo $order['STATUS']; ?>"><?php echo ucfirst($order['STATUS']); ?></span></td>
                                <td>
                                    <a href="/Nova/pages/admin/order-details.php?id=<?php echo $order['ORDER_ID']; ?>" class="btn btn-sm btn-secondary">Détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="view-all">
                    <a href="/Nova/pages/admin/orders.php" class="btn btn-primary">Voir toutes les commandes</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../../backend/includes/footer.php';
?>
