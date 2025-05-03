<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/models/User.php';

// Check if user is admin
if (!Session::get('is_admin')) {
    redirect('/pages/login.php');
    exit;
}

// Initialize user model
$userModel = new User();

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get users with pagination
$users = $userModel->getAllUsers($limit, $offset);
$totalUsers = $userModel->getTotalUsersCount();
$totalPages = ceil($totalUsers / $limit);

// Process admin status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'toggle_admin') {
        $user = $userModel->getUserById($userId);
        $isAdmin = $user['IS_ADMIN'] == 1 ? 0 : 1;
        $result = $userModel->updateAdminStatus($userId, $isAdmin);
        $statusMessage = $result ? 'Statut administrateur mis à jour avec succès.' : 'Erreur lors de la mise à jour du statut.';
    }
}

// Set page title
$pageTitle = 'Gérer les utilisateurs';

// Include header
require_once __DIR__ . '/../../backend/includes/header.php';
?>

<main class="container">
    <div class="admin-container">
        <?php require_once __DIR__ . '/../../backend/includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <h1>Gestion des utilisateurs</h1>
            
            <?php if (isset($statusMessage)): ?>
                <div class="alert <?php echo $result ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $statusMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($users)): ?>
                <div class="alert alert-info">Aucun utilisateur trouvé.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Nom</th>
                            <th>Date d'inscription</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['USER_ID']; ?></td>
                                <td><?php echo htmlspecialchars($user['USERNAME']); ?></td>
                                <td><?php echo htmlspecialchars($user['EMAIL']); ?></td>
                                <td>
                                    <?php 
                                        $fullName = trim(htmlspecialchars($user['FIRST_NAME'] . ' ' . $user['LAST_NAME']));
                                        echo !empty($fullName) ? $fullName : '<em>Non renseigné</em>';
                                    ?>
                                </td>
                                <td><?php echo isset($user['CREATED_AT']) ? date('d/m/Y', strtotime($user['CREATED_AT'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['IS_ADMIN'] == 1 ? 'completed' : 'pending'; ?>">
                                        <?php echo $user['IS_ADMIN'] == 1 ? 'Oui' : 'Non'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier les droits administrateur de cet utilisateur ?')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                            <input type="hidden" name="action" value="toggle_admin">
                                            <button type="submit" class="btn btn-<?php echo $user['IS_ADMIN'] == 1 ? 'danger' : 'primary'; ?> btn-sm">
                                                <?php echo $user['IS_ADMIN'] == 1 ? 'Retirer admin' : 'Faire admin'; ?>
                                            </button>
                                        </form>
                                        
                                        <a href="/Nova/pages/admin/user-orders.php?id=<?php echo $user['USER_ID']; ?>" class="btn btn-secondary btn-sm">Commandes</a>
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
