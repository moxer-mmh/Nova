<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../backend/models/User.php';
require_once __DIR__ . '/../backend/models/Order.php';

// Redirection si non connecté
Session::requireLogin();

// Titre de la page
$pageTitle = 'Mon compte';

// Récupération des informations de l'utilisateur
$userId = Session::get('user_id');
$user = new User();
$userInfo = $user->getUserById($userId);

// Récupération des commandes de l'utilisateur
$order = new Order();
$orders = $order->getUserOrders($userId);

// Traitement du formulaire de mise à jour du profil
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $postalCode = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'Le prénom et le nom sont requis';
    } else {
        if ($user->updateProfile($userId, $firstName, $lastName, $address, $city, $postalCode, $country, $phone)) {
            $success = 'Profil mis à jour avec succès';
            $userInfo = $user->getUserById($userId); // Rafraîchir les données
        } else {
            $error = 'Erreur lors de la mise à jour du profil';
        }
    }
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Tous les champs sont requis';
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        $error = 'Le nouveau mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Les nouveaux mots de passe ne correspondent pas';
    } else {
        // Vérification du mot de passe actuel
        if ($user->validateLogin($userInfo['USERNAME'], $currentPassword)) {
            if ($user->updatePassword($userId, $newPassword)) {
                $success = 'Mot de passe modifié avec succès';
            } else {
                $error = 'Erreur lors de la modification du mot de passe';
            }
        } else {
            $error = 'Mot de passe actuel incorrect';
        }
    }
}

// Inclusion de l'en-tête
require_once __DIR__ . '/../backend/includes/header.php';
?>

<div class="account-container">
    <h1>Mon compte</h1>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="account-tabs">
        <div class="tab-links">
            <a href="#profile" class="tab-link active">Profil</a>
            <a href="#orders" class="tab-link">Commandes</a>
            <a href="#password" class="tab-link">Mot de passe</a>
        </div>
        
        <div id="profile" class="tab-content active">
            <h2>Informations personnelles</h2>
            
            <form method="post" action="" class="account-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['FIRST_NAME'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['LAST_NAME'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" 
                           value="<?php echo htmlspecialchars($userInfo['EMAIL']); ?>" readonly disabled>
                    <small class="form-text text-muted">L'email ne peut pas être modifié</small>
                </div>
                
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <input type="text" id="address" name="address" class="form-control" 
                           value="<?php echo htmlspecialchars($userInfo['ADDRESS'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Ville</label>
                        <input type="text" id="city" name="city" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['CITY'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Code postal</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['POSTAL_CODE'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Pays</label>
                        <input type="text" id="country" name="country" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['COUNTRY'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($userInfo['PHONE'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour le profil</button>
            </form>
        </div>
        
        <div id="orders" class="tab-content">
            <h2>Mes commandes</h2>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>Vous n'avez pas encore passé de commande.</p>
                    <a href="/Nova/pages/search.php" class="btn btn-primary">Explorer le catalogue</a>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>N° de commande</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['ORDER_ID']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['ORDER_DATE'])); ?></td>
                                <td><span class="order-status <?php echo $order['STATUS']; ?>"><?php echo ucfirst($order['STATUS']); ?></span></td>
                                <td><?php echo formatPrice($order['TOTAL_AMOUNT']); ?></td>
                                <td>
                                    <a href="/Nova/pages/order-details.php?id=<?php echo $order['ORDER_ID']; ?>" class="btn btn-sm btn-secondary">Détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div id="password" class="tab-content">
            <h2>Changer le mot de passe</h2>
            
            <form method="post" action="" class="account-form">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <small class="form-text text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">Changer le mot de passe</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            
            // Masquer tous les contenus d'onglets
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les liens d'onglets
            tabLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            document.querySelector(targetId).classList.add('active');
            this.classList.add('active');
            
            // Mettre à jour l'URL avec un fragment
            history.pushState(null, null, targetId);
        });
    });
    
    // Activer l'onglet en fonction du fragment d'URL
    const hash = window.location.hash || '#profile';
    document.querySelector(`.tab-link[href="${hash}"]`)?.click();
});
</script>

<?php
// Inclusion du pied de page
require_once __DIR__ . '/../backend/includes/footer.php';
?>
